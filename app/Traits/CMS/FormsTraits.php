<?php

namespace App\Traits\CMS;

use App\Models\CMS\FormMaster;
use App\Models\CMS\FormMultiDet;
use App\Models\CMS\FormAnswerDet;
use App\Models\CMS\FormMasterTitle;
use App\Models\CMS\FormSetupDet;
use App\Models\CMS\FormShareDet;
use App\Models\CMS\FormLogicsDet;

use Illuminate\Support\Facades\DB;

use App\Traits\PORTAL\GencodeTraits;

trait FormsTraits
{
    use GencodeTraits;

    private function isJson($string)
    {
        if (!is_string($string)) {
            return false;
        }
        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }
    public function getHeaderAllForms($data)
    {
        $hasil = [];
        foreach ($data as $key => $value) {
            $answer = [];
            $exp = [];
            $answerID = [];
            foreach ($value['form_master'] as $key => $valueAns) {
                $cekAnswer = FormAnswerDet::where('cfmd_id', $valueAns['id'])->first();
                if (!empty($cekAnswer)) {
                    $answer[] = is_array(json_decode($cekAnswer['cfm_val'])) ? json_decode($cekAnswer['cfm_val']) : (int) $cekAnswer['cfm_val'];
                    $exp[] = $cekAnswer['cfm_exp'];
                } else {
                    $answer[] = '';
                    $exp[] = '';
                }
                $answerID[] = $valueAns['id'];
            }

            $shared = FormShareDet::where('cfmt_id', $value['id'])
                ->join('STX_PORTAL.dbo.portal_users_det', 'u_username', 'cfsd_to')
                ->leftjoin('STX_PORTAL.dbo.portal_app_mstr', 'am_app_url', DB::raw("CONCAT('forms/', cfsd_gen_link)"))
                ->where('pud_is_active', 1)
                ->get();

            $roleList = [];
            foreach ((clone $shared)->toArray() as $key => $value2) {
                $roleList[$value2['cfsd_role_id']] = (int) $value2['cfsd_role_id'];
            }

            // If the form is a quiz, get the setup from the quiz setup table
            if ($value['cfmt_quiz_flag'] == 1) {
                $setupTraining = !empty($value['quiz_setup'])
                    ? [
                        'defaultNumberOfChoice' => 1,
                        'defaultTypeChoice' => "multiple-radio",
                        'hourTimer' => (int) $value['quiz_setup']['cfsd_hours'],
                        'minTimer' => (int) $value['quiz_setup']['cfsd_min'],
                        'randomizeQuestion' => (boolean) $value['quiz_setup']['cfsd_rand_quest'],
                        'secTimer' => (int) $value['quiz_setup']['cfsd_sec'],
                        'setUpTimer' => (boolean) $value['quiz_setup']['cfsd_timer'],
                        'showResult' => (boolean) $value['quiz_setup']['cfsd_res_show'],
                        'showRightKeysAnswer' => (boolean) $value['quiz_setup']['cfsd_ans_show'],
                        'showRightKeysAnswerLocation' => $value['quiz_setup']['cfsd_ans_loc'],
                        'timerEveryQuestion' => (boolean) $value['quiz_setup']['cfsd_timer_quest'],
                        'minPass' => $value['quiz_setup']['cfsd_min_pass'],
                        'startQuiz' => $value['quiz_setup']['cfsd_start_quiz'],
                        'endQuiz' => $value['quiz_setup']['cfsd_end_quiz'],
                        'skipNextButtonMedia' => (boolean) $value['quiz_setup']['cfsd_skip_next_btn_media_done'],
                        'maxQuestionCount' => (int) $value['quiz_setup']['cfsd_quest_limit']
                    ]
                    : null;

                $setupTrainingRes = $setupTraining;
            } else {
                $setupTraining = $this->getDataGencode(
                    'FORMS_SETUP',
                    [
                        'pgm_value' => (string) $value['id']
                    ],
                    [
                        'pgm_desc' => 'pgm_value2'
                    ]
                );

                // Convert numeric 1/0 values in $setupTraining to boolean
                $setupTrainingRes = [];
                foreach ($setupTraining as $k => $v) {
                    if (is_string($v) && $this->isJson($v)) {
                        $setupTrainingRes[$k] = json_decode($v, true);
                    } else {
                        // Explicitly cast "1"/"0", 1/0, "true"/"false" to boolean, else keep original
                        if ($v === "1" || $v === 1 || $v === true || $v === "true") {
                            $setupTrainingRes[$k] = true;
                        } elseif ($v === "0" || $v === 0 || $v === false || $v === "false") {
                            $setupTrainingRes[$k] = false;
                        } else {
                            $setupTrainingRes[$k] = $v;
                        }
                        // Force boolean for 1/0 (int or string)
                        if ($v === 1 || $v === 0 || $v === "1" || $v === "0") {
                            $setupTrainingRes[$k] = (bool)$v;
                        }
                    }
                }
            }

            $hasil[] = [
                'id' => $value['id'],
                'title' => $value['cfmt_title'],
                'isQuiz' => $value['cfmt_quiz_flag'],
                'forms' => $this->convertToFE($value['form_master']),
                // 'checkFormMaster' => $value['form_master'],
                'ans' => $answer,
                'exp' => $exp,
                'ans_id' => $answerID,
                'share' => (clone $shared)->pluck('cfsd_to'),
                'setupTraining' => $setupTrainingRes,
                'shareFormsIsMainMenu' => count((clone $shared)) > 0 && (clone $shared)[0]->cfsd_is_menu == 1 ? true : false,
                'shareFormsIsRoles' => count((clone $shared)) > 0 && !empty((clone $shared)[0]->cfsd_role_id) ? true : false,
                'selectedSharedMenu' => count((clone $shared)) > 0 && !empty((clone $shared)[0]->cfsd_role_id) ? (clone $shared)[0]->am_app_parent : '',
                'shareFormsMenuIcon' => count((clone $shared)) > 0 && !empty((clone $shared)[0]->cfsd_role_id) ? (clone $shared)[0]->am_app_icon : '',
                'shareFormsRoleID' => count((clone $shared)) > 0 && !empty((clone $shared)[0]->cfsd_role_id) ? array_values($roleList) : '',
            ];
        }

        return $hasil;
    }

    public function convertToFE($data, $withAns = false): array
    {
        $hasil = [];
        foreach ($data as $key => $value) {
            $hasilDetail = [];
            if (isset($value['form_detail'])) {
                foreach ($value['form_detail'] as $keyDet => $valueDet) {
                    $hasilDetail[] = [
                        'col_det_id' => 'opt-' . $valueDet['id'],
                        'col_det_label' => '',
                        'value' => $valueDet['cfmd_value'],
                        'label' => $valueDet['cfmd_label'],
                    ];
                }
            }

            $dataLogics = FormLogicsDet::where('cfm_id', $value['id'])
                ->orderBy('cfld_seq_name', 'asc')
                ->get();

            $dataLogs = [];
            $keysData = 0;
            foreach ($dataLogics as $keyLogics => $item) {
                // if($keyLogics === 0) {
                //     $dataLogs[$item->cfld_seq_name] = [
                //         'seq_name' => $item->cfld_seq_name,
                //         'seq_desc' => $item->cfld_seq_desc,
                //         'data' => [],
                //     ];

                //     $keysData = 0;
                // }

                // $dataLogs[$item->cfld_seq_name] = [
                //     'seq_name' => $item->cfld_seq_name,
                //     'seq_desc' => $item->cfld_seq_desc,
                // ];

                // $dataLogs[$item->cfld_seq_name]['data'][] = [
                //     'cfld_opr' => $item->cfld_opr,
                //     'cfld_val' => $item->cfld_val,
                //     'cfld_opr_ctrl' => $item->cfld_opr_ctrl,
                //     'cfld_res' => $item->cfld_res,
                //     'cfld_actions' => $item->cfld_actions,
                // ];

                $dataLogs[$item->cfld_seq_name] = [
                    'seq_name' => $item->cfld_seq_name,
                    'seq_desc' => $item->cfld_seq_desc,
                    'data' => isset($dataLogs[$item->cfld_seq_name]['data'])
                        ? array_merge($dataLogs[$item->cfld_seq_name]['data'], [
                            [
                                'cfld_opr' => $item->cfld_opr,
                                'cfld_val' => $item->cfld_val,
                                'cfld_opr_ctrl' => $item->cfld_opr_ctrl,
                                'cfld_res' => $item->cfld_res,
                                'cfld_actions' => $item->cfld_actions,
                            ]
                        ])
                        : [
                            [
                                'cfld_opr' => $item->cfld_opr,
                                'cfld_val' => $item->cfld_val,
                                'cfld_opr_ctrl' => $item->cfld_opr_ctrl,
                                'cfld_res' => $item->cfld_res,
                                'cfld_actions' => $item->cfld_actions,
                            ]
                        ],
                ];

                $keysData++;
            }

            $hasil[] = [
                'id' => $value['id'],
                'type' => $value['cfm_type'],
                'required' => $value['cfm_type'] === 'form' ? ($value['cfm_required'] == 1) : false,
                'seq_name' => empty($value['cfm_seq_name']) ? $key + 1 : $value['cfm_seq_name'],
                'content' => $value['cfm_type'] === 'row'
                    ? $this->convertToFE($value['all_children_content'])
                    : (
                        $value['cfm_type'] === 'html'
                        ? $value['cfm_content']
                        : array_merge(json_decode($value['cfm_content'], true), ['detail_data' => $hasilDetail])
                    ),
                'logics' => array_values($dataLogs),
            ];
        }

        return $hasil;
    }

    public function storingForms($data, $uname, $keyAnswer = [], $keyExp = [], $idTitle = '', $parent = 0, $masterKeys = 0, $hasil = [])
    {
        if ($data['type'] === 'row') {
            $content = '';

            $insert = FormMaster::updateOrCreate([
                'id' => $data['id'],
            ], [
                'p_u_username' => $uname,
                'cfmt_id' => $idTitle,
                'cfm_type' => $data['type'],
                'cfm_seq_name' => $data['seq_name'],
                'cfm_content' => $content,
                'cfm_parent_id' => $parent,
            ]);

            if ($insert) {
                $dataCols = [];
                foreach ($data['content'] as $key => $value) {
                    $dataCols[] = $this->storingForms($value, $uname, $keyAnswer, $keyExp, $idTitle, $insert->id);
                }

                $hasil[] = [
                    'status' => true,
                    'data' => $dataCols
                ];
            } else {
                $hasil[] = [
                    'status' => false,
                    'data' => []
                ];
            }
        } else {
            if (is_array($data['content'])) {
                $content = json_encode($data['content']);
            } else {
                $content = $data['content'];
            }

            if (!empty($data['id'])) {
                $insert = FormMaster::updateOrCreate([
                    'id' => $data['id'],
                ], [
                    'p_u_username' => $uname,
                    'cfmt_id' => $idTitle,
                    'cfm_type' => $data['type'],
                    'cfm_seq_name' => isset($data['seq_name']) ? $data['seq_name'] : '',
                    'cfm_content' => $content,
                    'cfm_parent_id' => $parent,
                    'cfm_required' => $data['type'] === 'form' ? $data['required'] : 0,
                ]);
            } else {
                $insert = FormMaster::create([
                    'p_u_username' => $uname,
                    'cfmt_id' => $idTitle,
                    'cfm_type' => $data['type'],
                    'cfm_seq_name' => isset($data['seq_name']) ? $data['seq_name'] : '',
                    'cfm_content' => $content,
                    'cfm_parent_id' => $parent,
                    'cfm_required' => $data['type'] === 'form' ? $data['required'] : 0,
                ]);
            }

            if ($insert) {
                $detail_data = [];
                if ($data['type'] === 'form' && isset($data['content']['detail_data']) && count($data['content']['detail_data']) > 0) {
                    foreach ($data['content']['detail_data'] as $key => $valueDet) {
                        $detail_data[] = FormMultiDet::updateOrCreate([
                            'cfm_id' => $insert->id,
                            'cfmd_value' => $valueDet['value'],
                        ], [
                            'cfm_id' => $insert->id,
                            'cfmd_value' => $valueDet['value'],
                            'cfmd_label' => $valueDet['label'],
                        ]);
                    }
                }

                $detail_data_key_ans = [];
                if ($data['type'] === 'form' && count($keyAnswer) > 0) {
                    foreach ($keyAnswer as $keyAns => $valueAns) {
                        if ($keyAns === $masterKeys) {
                            $getIDDetail = array_values(array_filter($detail_data, function ($f) use ($valueAns) {
                                $comp = is_array($f->cfmd_value) ? json_encode($f->cfmd_value) : $f->cfmd_value;
                                if ($comp == is_array($valueAns) ? json_encode($valueAns) : $valueAns) {
                                    return $f;
                                }
                            }));

                            if (is_array($valueAns)) {
                                $valnya = [];
                                foreach ($valueAns as $keyAnsArr => $valueAnsArr) {
                                    $valnya[] = (string) $valueAnsArr;
                                }
                            } else {
                                $valnya = $valueAns;
                            }

                            if (is_array($valueAns)) {
                                $hasilValue = [];
                                foreach ($valueAns as $keyAnswers => $valueAnswers) {
                                    $hasilValue[(int) $valueAnswers] = (string) $valueAnswers;
                                }

                                $hasilValue = json_encode(array_values($hasilValue));
                            } else {
                                $hasilValue = $valueAns;
                            }
                            $detail_data_key_ans[] = FormAnswerDet::updateOrCreate([
                                'cfm_id' => $idTitle,
                                'cfmd_id' => $insert->id,
                                // 'cfm_val' => is_array($valueAns) ? (string) json_encode($valueAns) : (string) $valueAns,
                            ], [
                                'p_u_username' => $uname,
                                'cfm_id' => $idTitle,
                                'cfmd_id' => $insert->id,
                                'cfm_val' => (string) $hasilValue,
                                'cfm_exp' => isset($keyExp[$keyAns]) ? (string) $keyExp[$keyAns] : null,
                            ]);
                        }
                    }
                }

                if (isset($data['logics'])) {
                    foreach ($data['logics'] as $keyLogics => $valueLogics) { //Split by id sequences
                        $getLastLogics = FormLogicsDet::where('cfm_id', $insert->id)->orderBy('created_at', 'desc')->first();

                        if (isset($valueLogics['seq_name']) && !empty($valueLogics['seq_name'])) {
                            $createNewSeqName = $valueLogics['seq_name'];
                        } else {
                            $createNewSeqName = empty($getLastLogics) ? 'L' . $insert->id . '-0001' : 'L' . $insert->id . '-' . str_pad((int) substr($getLastLogics->cfld_seq_name, 5) + 1, 4, '0', STR_PAD_LEFT);
                        }

                        foreach ($valueLogics['data'] as $key => $valueLogicsDet) {
                            FormLogicsDet::updateOrCreate([
                                'cfld_seq_name' => $createNewSeqName,
                                'cfm_id' => $insert->id,
                                'cfld_actions' => $valueLogicsDet['cfld_actions'],
                                'cfld_opr' => $valueLogicsDet['cfld_opr'],
                                'cfld_val' => $valueLogicsDet['cfld_val'],
                            ], [
                                'cfm_id' => $insert->id,
                                'cfld_seq_name' => $createNewSeqName,
                                'cfld_seq_desc' => $valueLogics['seq_desc'],
                                'cfld_opr' => $valueLogicsDet['cfld_opr'],
                                'cfld_val' => $valueLogicsDet['cfld_val'],
                                'cfld_opr_ctrl' => $valueLogicsDet['cfld_opr_ctrl'],
                                'cfld_res' => $valueLogicsDet['cfld_res'],
                                'cfld_actions' => $valueLogicsDet['cfld_actions'],
                            ]);
                        }
                    }
                }

                $hasil[] = [
                    'status' => true,
                    'data' => [
                        'master' => $insert,
                        'detail_multiple' => $detail_data,
                        'detail_ans' => $detail_data_key_ans
                    ]
                ];
            } else {
                $hasil[] = [
                    'status' => false,
                    'data' => []
                ];
            }
        }

        return $hasil;
    }
}
