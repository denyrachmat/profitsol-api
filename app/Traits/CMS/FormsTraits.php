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

trait FormsTraits
{
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
                    $answer[] = is_array(json_decode($cekAnswer['cfm_val'])) ? json_decode($cekAnswer['cfm_val']) : (int)$cekAnswer['cfm_val'];
                    $exp[] = $cekAnswer['cfm_exp'];
                } else {
                    $answer[] = '';
                    $exp[] = '';
                }
                $answerID[] = $valueAns['id'];
            }

            $shared = FormShareDet::where('cfmt_id', $value['id'])
                ->leftjoin('STX_PORTAL.dbo.portal_app_mstr', 'am_app_url', DB::raw("CONCAT('forms/', cfsd_gen_link)"))
                ->get();

            $roleList = [];
            foreach ((clone $shared)->toArray() as $key => $value2) {
                $roleList[$value2['cfsd_role_id']] = (int) $value2['cfsd_role_id'];
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
                'setupTraining' => !empty($value['quiz_setup'])
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
                        'skipNextButtonMedia' => (boolean)$value['quiz_setup']['cfsd_skip_next_btn_media_done'],
                        'maxQuestionCount' => (int) $value['quiz_setup']['cfsd_quest_limit']
                    ]
                    : null,
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
            ],[
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

            if(!empty($data['id'])) {
                $insert = FormMaster::updateOrCreate([
                    'id' => $data['id'],
                ],[
                    'p_u_username' => $uname,
                    'cfmt_id' => $idTitle,
                    'cfm_type' => $data['type'],
                    'cfm_seq_name' => isset($data['seq_name']) ? $data['seq_name'] : '',
                    'cfm_content' => $content,
                    'cfm_parent_id' => $parent,
                    'cfm_required' => $data['type'] === 'form' ? $data['required'] : 0,
                ]);
            } else {
                $insert = FormMaster::updateOrCreate([
                    'id' => $data['id'],
                ],[
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
                        ],[
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

                            if(is_array($valueAns)) {
                                $hasilValue = [];
                                foreach ($valueAns as $keyAnswers => $valueAnswers) {
                                    $hasilValue[(int)$valueAnswers] = (string)$valueAnswers;
                                }

                                $hasilValue = json_encode(array_values($hasilValue));
                            } else {
                                $hasilValue = $valueAns;
                            }
                            $detail_data_key_ans[] = FormAnswerDet::updateOrCreate([
                                'cfm_id' => $idTitle,
                                'cfmd_id' => $insert->id,
                                // 'cfm_val' => is_array($valueAns) ? (string) json_encode($valueAns) : (string) $valueAns,
                            ],[
                                'p_u_username' => $uname,
                                'cfm_id' => $idTitle,
                                'cfmd_id' => $insert->id,
                                'cfm_val' => (string)$hasilValue,
                                'cfm_exp' => isset($keyExp[$keyAns]) ? (string) $keyExp[$keyAns] : null,
                            ]);
                        }
                    }
                }

                if (isset($data['logics'])) {
                    foreach ($data['logics'] as $keyLogics => $valueLogics) {
                        FormLogicsDet::updateOrCreate([
                            'id' => $data['id'],
                        ],[
                            'cfm_id' => $insert->id,
                            'cfld_opr' => $valueLogics['opr'],
                            'cfld_val' => $valueLogics['modelValue'],
                            'cfld_opr_ctrl' => $valueLogics['oprCont'],
                            'cfld_res' => $valueLogics['result'],
                            'cfld_actions' => json_encode($valueLogics['resultAction']),
                        ]);
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
