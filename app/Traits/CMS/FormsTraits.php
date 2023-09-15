<?php

namespace App\Traits\CMS;

use App\Models\CMS\FormMaster;
use App\Models\CMS\FormMultiDet;
use App\Models\CMS\FormAnswerDet;
use App\Models\CMS\FormMasterTitle;
use App\Models\CMS\FormSetupDet;
use App\Models\CMS\FormShareDet;

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
                if (isset($cekAnswer['cfm_exp']) && !empty($cekAnswer['cfm_exp'])) {
                    $answer[] = is_array(json_decode($cekAnswer['cfm_val'])) ? json_decode($cekAnswer['cfm_val']) : $cekAnswer['cfm_val'];
                    $exp[] = $cekAnswer['cfm_exp'];
                    $answerID[] = $valueAns['id'];
                }
            }

            $shared = FormShareDet::where('cfmt_id', $value['id'])->get()->pluck('cfsd_to');

            $hasil[] = [
                'id' => $value['id'],
                'title' => $value['cfmt_title'],
                'isQuiz' => $value['cfmt_quiz_flag'],
                'forms' => $this->convertToFE($value['form_master']),
                'ans' => $answer,
                'exp' => $exp,
                'ans_id' => $answerID,
                'share' => $shared,
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
                ]
                : null
            ];
        }

        return $hasil;
    }

    public function convertToFE($data): array
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
                'seq_name' => $value['cfm_seq_name'],
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

            $insert = FormMaster::create([
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

            $insert = FormMaster::create([
                'p_u_username' => $uname,
                'cfmt_id' => $idTitle,
                'cfm_type' => $data['type'],
                'cfm_seq_name' => isset($data['seq_name']) ? $data['seq_name'] : '',
                'cfm_content' => $content,
                'cfm_parent_id' => $parent,
                'cfm_required' => $data['type'] === 'form' ? $data['required'] : 0,
            ]);

            if ($insert) {
                $detail_data = [];
                if (isset($data['content']['detail_data']) && count($data['content']['detail_data']) > 0) {
                    foreach ($data['content']['detail_data'] as $key => $valueDet) {
                        $detail_data[] = FormMultiDet::create([
                            'cfm_id' => $insert->id,
                            'cfmd_value' => $valueDet['value'],
                            'cfmd_label' => $valueDet['label'],
                        ]);
                    }
                }

                $detail_data_key_ans = [];
                if (count($keyAnswer) > 0) {
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

                            $detail_data_key_ans[] = FormAnswerDet::create([
                                'p_u_username' => $uname,
                                'cfm_id' => $idTitle,
                                'cfmd_id' => $insert->id,
                                'cfm_val' => is_array($valueAns) ? (string)json_encode($valueAns) : (string)$valueAns,
                                'cfm_exp' => (string)$keyExp[$keyAns],
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