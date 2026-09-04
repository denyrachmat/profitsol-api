<?php

namespace App\Traits\CMS;

use App\Models\CMS\FormMaster;
use App\Models\CMS\FormMultiDet;
use App\Models\CMS\FormAnswerDet;
use App\Models\CMS\FormMasterTitle;
use App\Models\CMS\FormSetupDet;
use App\Models\CMS\FormShareDet;
use App\Models\CMS\FormLogicsDet;
use App\Models\MRS\MRSReportMstr;
use App\Models\PORTAL\PortalGencode;
use App\Models\PORTAL\PortalApp;
use App\Models\CMS\FormAnswerUserDet;
use Illuminate\Support\Facades\DB;

use App\Traits\PORTAL\GencodeTraits;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Exports\MRS\ExportReport;

use Excel;

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
                    $answer[] = is_array(json_decode($cekAnswer['cfm_val'])) 
                        ? json_decode($cekAnswer['cfm_val']) 
                        : (is_numeric($cekAnswer['cfm_val']) ? (int) $cekAnswer['cfm_val'] : $cekAnswer['cfm_val']); // skipped: JSON array parsing, add when specific handling of JSON arrays in `cfm_val` is required.
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
                if (!empty($value2['cfsd_role_id'])) $roleList[$value2['cfsd_role_id']] = (int) $value2['cfsd_role_id'];
            }
            $menuApp = PortalApp::where('am_app_code', 'FRM-' . $value['id'])->first();

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
                $setupTrainingRes = $this->getSetupFormsForForm($value['id']);
            }

            $getDataGencode = $this->getDataGencode(
                'URL_PAGE_GEN',
                ['pgm_value' => $value['id']],
                [
                    'url' => 'pgm_desc|string',
                    'desc' => 'pgm_desc2|string'
                ],
                [],
                true,
                false
            );

            $hasil[] = [
                'id' => $value['id'],
                'title' => $value['cfmt_title'],
                'desc' => $getDataGencode['desc'] ?? null,
                'isQuiz' => $value['cfmt_quiz_flag'],
                'status' => $value['cfmt_status'] ?? 'draft',
                'year' => $value['cfmt_year'] ?? null,
                'forms' => $this->convertToFE($value['form_master']),
                'checkFormMaster' => $value['form_master'],
                'ans' => $answer,
                'exp' => $exp,
                'ans_id' => $answerID,
                'share' => (clone $shared)->pluck('cfsd_to'),
                'setupTraining' => $setupTrainingRes,
                'shareFormsIsMainMenu' => $menuApp ? true : (count((clone $shared)) > 0 && (clone $shared)[0]->cfsd_is_menu == 1 ? true : false),
                'shareFormsIsRoles' => count((clone $shared)) > 0 && !empty((clone $shared)[0]->cfsd_role_id) ? true : (!empty($roleList) ? true : false),
                'selectedSharedMenu' => $menuApp ? ($menuApp->am_app_parent ?? '') : (count((clone $shared)) > 0 && isset((clone $shared)[0]->am_app_parent) ? (clone $shared)[0]->am_app_parent : ''),
                'shareFormsMenuIcon' => $menuApp ? ($menuApp->am_app_icon ?? '') : (count((clone $shared)) > 0 && isset((clone $shared)[0]->am_app_icon) ? (clone $shared)[0]->am_app_icon : ''),
                'shareFormsRoleID' => !empty($roleList) ? array_values($roleList) : (count((clone $shared)) > 0 && !empty((clone $shared)[0]->cfsd_role_id) ? array_values($roleList) : ''),
                'connectedMRS' => $this->getConnectedMRS((string) $value['id']),
                'created_at' => $value['created_at'],
                'tags' => $value['tags'] ?? [],
                'url' => $value['url'] ?? '',
                'hashtags' => $value['hashtags'] ?? [],
                'p_u_username' => $value['p_u_username'] ?? '',
                'subscription' => $value['subscription'] ?? [],
                'publish_list' => $value['publish_list'] ?? []
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
                ->orderBy('cfld_order', 'asc')
                ->get();

            $dataLogs = [];
            $keysData = 0;
            foreach ($dataLogics as $keyLogics => $item) {
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

            $style = $this->getDataGencode('CFM_STYLE_ATTR', [
                'pgm_value' => $value['id'],
                'pgm_value2' => 'width'
            ], [
                'width' => 'pgm_value3|string'
            ], [], true);

            $hasil[] = array_merge([
                'id' => $value['id'],
                'type' => $value['cfm_type'],
                'required' => $value['cfm_type'] === 'form' ? ($value['cfm_required'] == 1) : false,
                'seq_name' => empty($value['cfm_seq_name']) ? $key + 1 : (int) $value['cfm_seq_name'],
                'content' => $value['cfm_type'] === 'row'
                    ? $this->convertToFE($value['all_children_content'])
                    : (
                        $value['cfm_type'] === 'html'
                        ? $value['cfm_content']
                        : array_merge(json_decode($value['cfm_content'], true), ['detail_data' => $hasilDetail])
                    ),
                'logics' => array_values($dataLogs),
            ], $style);
        }

        return $hasil;
    }

    public function storingForms($data, $uname, $keyAnswer = [], $keyExp = [], $idTitle = '', $parent = 0, $masterKeys = 0, $hasil = [])
    {
        if ($data['type'] === 'row') {
            $content = '';

            if (isset($data['id'])) {
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
            } else {
                $insert = FormMaster::create([
                    'p_u_username' => $uname,
                    'cfmt_id' => $idTitle,
                    'cfm_type' => $data['type'],
                    'cfm_seq_name' => $data['seq_name'],
                    'cfm_content' => $content,
                    'cfm_parent_id' => $parent,
                ]);
            }

            if ($insert) {
                $dataCols = [];
                foreach ($data['content'] as $key => $value) {
                    // Add seq_name key with value $key + 1
                    $value['seq_name'] = $key + 1;
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
                    'cfm_seq_name' => isset($data['seq_name']) ? (int) $data['seq_name'] : '',
                    'cfm_content' => $content,
                    'cfm_parent_id' => $parent,
                    'cfm_required' => $data['type'] === 'form' ? $data['required'] : 0,
                ]);
            } else {
                $insert = FormMaster::create([
                    'p_u_username' => $uname,
                    'cfmt_id' => $idTitle,
                    'cfm_type' => $data['type'],
                    'cfm_seq_name' => isset($data['seq_name']) ? (int) $data['seq_name'] : '',
                    'cfm_content' => $content,
                    'cfm_parent_id' => $parent,
                    'cfm_required' => $data['type'] === 'form' ? $data['required'] : 0,
                ]);
            }

            if (isset($data['width']) && !empty($data['width'])) {
                PortalGencode::where('pgm_code', 'CFM_STYLE_ATTR')
                    ->where(DB::raw('CAST(pgm_value2 AS VARCHAR)'), 'width')
                    ->where(DB::raw('CAST(pgm_value AS VARCHAR)'), (string) $insert->id)
                    ->delete();

                PortalGencode::create([
                    'pgm_code' => 'CFM_STYLE_ATTR',
                    'pgm_value2' => 'width',
                    'pgm_value' => (string) $insert->id,
                    'pgm_value3' => (string) $data['width'],
                    'pgm_desc' => 'For width custom cols'
                ]);
            }

            if (isset($data['style']) && !empty($data['style'])) {
                PortalGencode::where('pgm_code', 'CFM_STYLE_ATTR')
                    ->where(DB::raw('CAST(pgm_value2 AS VARCHAR)'), 'style')
                    ->where(DB::raw('CAST(pgm_value AS VARCHAR)'), (string) $insert->id)
                    ->delete();

                PortalGencode::create([
                    'pgm_code' => 'CFM_STYLE_ATTR',
                    'pgm_value2' => 'style',
                    'pgm_value' => (string) $insert->id,
                    'pgm_value3' => json_encode($data['style']),
                    'pgm_desc' => 'For style custom cols'
                ]);
            }

            if ($insert) {
                $detail_data = [];
                if ($data['type'] === 'form' && isset($data['content']['detail_data']) && count($data['content']['detail_data']) > 0) {
                    FormMultiDet::where('cfm_id', $insert->id)->delete();

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
                    FormLogicsDet::where('cfm_id', $insert->id)->delete();

                    foreach ($data['logics'] as $keyLogics => $valueLogics) { //Split by id sequences
                        $getLastLogics = FormLogicsDet::where('cfm_id', $insert->id)->orderBy('cfld_order', 'desc')->first();

                        if (isset($valueLogics['seq_name']) && !empty($valueLogics['seq_name'])) {
                            $createNewSeqName = $valueLogics['seq_name'];
                        } else {
                            $createNewSeqName = empty($getLastLogics) ? 'L' . $insert->id . '-0001' : 'L' . $insert->id . '-' . str_pad((int) substr($getLastLogics->cfld_seq_name, 5) + 1, 4, '0', STR_PAD_LEFT);
                        }

                        foreach ($valueLogics['data'] as $key => $valueLogicsDet) {
                            FormLogicsDet::create([
                                'cfm_id' => $insert->id,
                                'cfld_seq_name' => $createNewSeqName,
                                'cfld_seq_desc' => $valueLogics['seq_desc'],
                                'cfld_opr' => $valueLogicsDet['cfld_opr'],
                                'cfld_val' => $valueLogicsDet['cfld_val'],
                                'cfld_opr_ctrl' => $valueLogicsDet['cfld_opr_ctrl'],
                                'cfld_res' => $valueLogicsDet['cfld_res'],
                                'cfld_actions' => $valueLogicsDet['cfld_actions'],
                                'cfld_order' => $key + 1,
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

    public function showHistory(Request $request, $id, $batchID = '')
    {
        $getData = PortalGencode::where('pgm_code', 'FORMS_SETUP')
            ->where('pgm_value', $id);

        $checkHist = (clone $getData)->where('pgm_desc', 'isHistory')->first();

        if (!empty($checkHist) && $checkHist->pgm_value2 == 1) {
            $columns = (clone $getData)->where('pgm_desc', 'historyTableList')->pluck('pgm_value2')->first();
            $colList = $request->has('histTableList') ? $request->input('histTableList') : json_decode($columns);
            $values = [];
            foreach ($colList as $col) {
                if (isset($col->value)) {
                    $values[] = (int) $col->value;
                }
            }

            $dataKeys = FormMaster::select(
                'cms_form_mstr.cfm_parent_id',
                'cms_form_mstr.id',
                'parent_form.cfm_seq_name'
            )->whereIn('cms_form_mstr.id', $values)
                ->with('parentContent')
                ->join(DB::raw('cms_form_mstr as parent_form'), 'parent_form.id', '=', 'cms_form_mstr.cfm_parent_id')
                ->orderBy(DB::raw('CAST(parent_form.cfm_seq_name AS INT)'))
                ->get()
                // ->pluck('cfm_seq_name', 'id')
                ->toArray();

            $hasilKeys = [];
            foreach ($dataKeys as $keyPos => $valuePos) {
                $hasilKeys[] = [
                    'id' => $valuePos['id'],
                    'parent' => $valuePos['cfm_parent_id'],
                ];
            }

            $columns = collect(json_decode($columns))
                ->sortBy('value')
                ->values()
                ->toJson();

            $data = FormAnswerUserDet::select(
                'cms_form_ans_user_det.p_u_username',
                'cms_form_ans_user_det.cfm_id',
                'cms_form_ans_user_det.cfmd_id',
                DB::raw('CAST(cms_form_ans_user_det.cfm_val AS VARCHAR(MAX)) as cfm_val'),
                'cms_form_ans_user_det.created_at',
                'cfaud_batch',
                'prh_prmid',
                'prh_flag',
                'prh_result',
                DB::raw('portaL_rpa_hist.id as prh_id')
            )
                ->leftJoin('STX_PORTAL.dbo.portaL_rpa_hist', function ($f) {
                    $f->on('portaL_rpa_hist.prh_cfaud_batch_id', '=', 'cms_form_ans_user_det.cfaud_batch')
                        ->on('portaL_rpa_hist.prh_cfaud_id', '=', 'cms_form_ans_user_det.cfm_id');
                })
                ->leftJoin('cms_form_ams_map_det', function ($f) {
                    $f->on('cms_form_ams_map_det.cfamd_cfm_id', '=', 'cms_form_ans_user_det.cfm_id')
                        ->on('cms_form_ams_map_det.cfamd_cfaud_batch', '=', 'cms_form_ans_user_det.cfaud_batch');
                })
                ->leftJoin('STX_AMS.dbo.ams_apprv_hist_det', function ($f) {
                    $f->on('ams_apprv_hist_det.amstd_token', '=', 'cms_form_ams_map_det.cfamd_amstd_token');
                })
                // Add where if $batchID is not empty
                ->when(!empty($batchID), function ($query) use ($batchID) {
                    $query->where('cms_form_ans_user_det.cfaud_batch', $batchID);
                })
                // ->leftJoin('')
                ->where('cms_form_ans_user_det.cfm_id', $id)
                ->groupBy(
                    'cms_form_ans_user_det.p_u_username',
                    'cms_form_ans_user_det.cfm_id',
                    'cms_form_ans_user_det.cfmd_id',
                    DB::raw('CAST(cms_form_ans_user_det.cfm_val AS VARCHAR(MAX))'),
                    'cms_form_ans_user_det.created_at',
                    'cfaud_batch',
                    'prh_prmid',
                    'prh_flag',
                    'prh_result',
                    'portaL_rpa_hist.id'
                )
                ->orderBy('cfaud_batch', 'asc')
                // ->orderBy('cfm_seq_name', 'asc')
                ->get();

            // return $data;

            $resCols = [];
            if (empty($columns)) {
                $resCols = [];
            } else {
                foreach (json_decode($columns) as $key => $Colvalue) {

                    $dataContent = FormMaster::where(DB::raw('CAST(id AS VARCHAR)'), (string) $Colvalue->value)->first();

                    if (!empty($dataContent)) {
                        $content = json_decode($dataContent->cfm_content);

                        $Colvalue->component = $content->component;
                        $Colvalue->parent = $dataContent->cfm_parent_id;
                    }

                    $resCols[] = $Colvalue;
                }
            }

            $columns = json_encode($resCols);

            $getArrPos = [];
            $colIdx = 0;
            $rowIdx = -1;
            foreach ($hasilKeys as $key => $valueArr) {
                if (isset($valueArr['parent'])) {
                    if ($key > 0 && $valueArr['parent'] === $hasilKeys[$key - 1]['parent'])
                        $colIdx++;
                    else
                        $rowIdx++;

                    $getArrPos[$valueArr['id']] = [$rowIdx, $colIdx];

                }
            }

            $result = [];
            foreach ($data as $key => $value) {
                foreach (json_decode($columns) as $column) {
                    // $getDataValue = $value->cfmd_id == $column->value ? $value->cfm_val : '';
                    if ($value->cfmd_id == $column->value) {
                        $progress = $value->prh_flag == 1 ? 'Completed' : ($value->prh_flag == 2 ? 'In Progress' : 'Not Started');

                        $result[$value->cfaud_batch]['form_id'] = $value->cfm_id;
                        $result[$value->cfaud_batch]['batch_id'] = $value->cfaud_batch;
                        $result[$value->cfaud_batch]['progress'] = $progress;
                        $result[$value->cfaud_batch]['progress_detail'] = $value->prh_result ?? '';
                        $result[$value->cfaud_batch]['created_by'] = $value->p_u_username;
                        $result[$value->cfaud_batch]['created_at'] = $value->created_at;
                        if (strpos($value->cfm_val, 'data:application') === 0) {
                            $result[$value->cfaud_batch]['CMS_REPORT_' . $column->value] = 'file:cms/openFiles/' . $value->cfm_id . '/' . $value->cfaud_batch;
                        } else {
                            $showAs = isset($column->showAs) ? $column->showAs : null;
                            if ($showAs === 'value' || empty($showAs)) {
                                $result[$value->cfaud_batch]['CMS_REPORT_' . $column->value] = $value->cfm_val;
                            } else {
                                $apiOpt = isset($column->component) && isset($column->component->apiOpt) ? $column->component->apiOpt : [];
                                if (isset($column->component->apiOpt)) {
                                    $selectedKeys = isset($column->component) && isset($column->component->apiOpt->selectedKeys) ? $column->component->apiOpt->selectedKeys : (object) [];
                                    $selectedKey = isset($selectedKeys->{$showAs}) ? $selectedKeys->{$showAs} : 'value';
                                    $apiResult = $this->searchDataOnAPI($apiOpt, $value->cfm_val, $result[$value->cfaud_batch]);
                                    $result[$value->cfaud_batch]['CMS_REPORT_' . $column->value] = $apiResult[$selectedKey] ?? '';
                                } else {
                                    $selected = collect($column->forms->content->detail_data ?? [])
                                        ->first(function ($f) use ($value) {
                                            return isset($f->value) && $f->value == $value->cfm_val;
                                        });

                                    $result[$value->cfaud_batch]['CMS_REPORT_' . $column->value] = $selected && isset($selected->{$showAs})
                                        ? $selected->{$showAs}
                                        : ($selected->label ?? $selected->value ?? '');
                                }

                            }
                        }

                        $result[$value->cfaud_batch]['CMS_REPORT_POS_' . $column->value] = $getArrPos[$column->value] ?? [];
                        $result[$value->cfaud_batch]['CMS_REPORT_VAL_' . $column->value] = $value->cfm_val;
                        $result[$value->cfaud_batch]['prh_id'] = $value->prh_id;
                        $result[$value->cfaud_batch]['prh_flag'] = $value->prh_flag;
                        // $result[$value->cfaud_batch]['CMS_REPORT_' . $column->value . '_API'] = $this->searchDataOnAPI($column->component->apiOpt ?? [], $value->cfm_val, $result[$value->cfaud_batch]);
                    }
                }
            }

            $result = collect($result);

            if ($request->has('filter') && count($request->filter) > 0) {
                foreach ($request->filter as $keyFilter => $valueFilter) {
                    // Check if filter value contains function calls like today()
                    if (isset($valueFilter['operator']) && in_array(strtolower($valueFilter['operator']), ['isnull', 'isnotnull'])) {
                        // Handle null/not null operators without needing a value
                        if (strtolower($valueFilter['operator']) === 'isnull') {
                            $result = $result->filter(function ($item) use ($valueFilter) {
                                return !isset($item[$valueFilter['column']]) || empty($item[$valueFilter['column']]);
                            });
                        } else {
                            $result = $result->filter(function ($item) use ($valueFilter) {
                                return isset($item[$valueFilter['column']]) && !empty($item[$valueFilter['column']]);
                            });
                        }
                    } elseif (isset($valueFilter['value']) && !empty($valueFilter['value'])) {
                        $valueFilter['value'] = $this->evaluateFilterValue($valueFilter['value']);
                    }

                    if (isset($valueFilter['value']) && !empty($valueFilter['value'])) {
                        if (isset($valueFilter['operator']) && strtolower($valueFilter['operator']) === 'like') {
                            $result = $result->filter(function ($item) use ($valueFilter) {
                                return isset($item[$valueFilter['column']]) &&
                                    stripos($item[$valueFilter['column']], $valueFilter['value']) !== false;
                            });
                        } else {
                            $result = $result->where($valueFilter['column'], $valueFilter['operator'], $valueFilter['value']);
                        }
                    }
                }
            }

            if ($request->has('pagination') && is_array($request->pagination)) {
                $page = isset($request->pagination['page']) ? (int) $request->pagination['page'] : 1;
                $perPage = isset($request->pagination['rowsPerPage']) ? (int) $request->pagination['rowsPerPage'] : 10;
                $totalCount = $result->count();
                $perPage = $perPage === 0 ? $totalCount : $perPage;
                $result = $result->forPage($page, $perPage);
                $result = new \Illuminate\Pagination\LengthAwarePaginator(
                    $result->values(),
                    $totalCount,
                    $perPage,
                    $page,
                    ['path' => $request->url(), 'query' => $request->query()]
                );

                // Custom pagination response
                $pagination = [
                    'total' => $result->total(),
                    'lastPage' => $result->lastPage(),
                    'page' => $page,
                    'rowsNumber' => $result->total(),
                    'rowsPerPage' => $perPage,
                    'sortBy' => $request->input('pagination.sortBy', ''),
                    'data' => $result->items(),
                ];

                return $this->handleResponse($pagination, 'Data found !');
            }

            $result = [
                'columns' => json_decode($columns),
                'data' => !empty($showHistory) ? $result->values()->toArray()[0] : $result->values()->toArray(),
            ];

            return $this->handleResponse($result, 'Data found !');
        } else {
            return $this->handleError([], 'This form is not set to history !');
        }
    }

    public function evaluateFilterValue($value)
    {
        // Check for today() function
        if (preg_match('/today\(\)/i', $value)) {
            $today = date('Y-m-d');
            $value = preg_replace('/today\(\)/i', $today, $value);
        }

        // Add more function evaluations as needed

        return $value;
    }

    public function searchDataOnAPI($apiOpt, $value = '', $result = [])
    {
        // If apiOpt is empty, return the result as is
        if (empty($apiOpt)) {
            return $result;
        }

        // If apiOpt is not an array, convert it to an array
        if (!is_array($apiOpt)) {
            $apiOpt = (array) $apiOpt;
        }

        // If apiOpt does not have 'api_params', return the result as is
        if (
            (is_array($apiOpt) && !isset($apiOpt['api_params'])) &&
            (!is_object($apiOpt) || (is_object($apiOpt) && !isset($apiOpt->api_params)))
        ) {
            return $result;
        }

        // Build the request parameters
        $params = $this->buildNestedParams(is_array($apiOpt) ? $apiOpt['api_params'] : $apiOpt->api_params);

        // Add the value to the parameters if it is not empty
        if (!empty($value)) {
            if (is_array($apiOpt)) {
                if (isset($apiOpt['selectedKeys'])) {
                    if (is_array($apiOpt['selectedKeys'])) {
                        $selectedKey = $apiOpt['selectedKeys']['value'] ?? ($apiOpt['selectedKeys']['label'] ?? null);
                    } elseif (is_object($apiOpt['selectedKeys'])) {
                        $selectedKey = $apiOpt['selectedKeys']->value ?? ($apiOpt['selectedKeys']->label ?? null);
                    } else {
                        $selectedKey = null;
                    }
                } else {
                    $selectedKey = null;
                }
            } elseif (is_object($apiOpt)) {
                if (isset($apiOpt->selectedKeys)) {
                    if (is_array($apiOpt->selectedKeys)) {
                        $selectedKey = $apiOpt->selectedKeys['value'] ?? ($apiOpt->selectedKeys['label'] ?? null);
                    } elseif (is_object($apiOpt->selectedKeys)) {
                        $selectedKey = $apiOpt->selectedKeys->value ?? ($apiOpt->selectedKeys->label ?? null);
                    } else {
                        $selectedKey = null;
                    }
                } else {
                    $selectedKey = null;
                }
            } else {
                $selectedKey = null;
            }

            $params['filters'][] = [
                'cols' => $selectedKey,
                'param' => '=',
                'value' => $value
            ];
        }

        foreach ($params['filters'] as $key => $filter) {
            $params['filters'][$key]['value'] = $result[$filter['value']] ?? $filter['value'];
        }

        // return $params;

        // Make the API call
        $client = new Client();

        try {
            $method = is_array($apiOpt) ? $apiOpt['api_method'] : $apiOpt->api_method;
            $url = is_array($apiOpt) ? $apiOpt['api_url'] : $apiOpt->api_url;
            $selectedNode = is_array($apiOpt) ? ($apiOpt['selectedNode'] ?? []) : ($apiOpt->selectedNode ?? []);

            $response = $client->request($method, $url, [
                'json' => $params,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            // Process selected node if needed
            if (!empty($selectedNode)) {
                foreach ($selectedNode as $key) {
                    $data = $data[$key] ?? null;
                    if ($data === null)
                        break;
                }
            }

            return $data;

        } catch (\Exception $e) {
            // Handle exception
            \Log::error('API call failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Build nested params from flat array/object with dot notation keys.
     * Supports keys like "params.data" => "value" and ["param_name" => "params.data", ...]
     * @param array $apiParams
     * @return array
     */
    protected function buildNestedParams(array $apiParams, $cfmId = '', $batchID = ''): array
    {
        $result = [];

        // If the input is a flat associative array (not a list of param objects)
        $isAssoc = function ($arr) {
            if ([] === $arr)
                return false;
            return array_keys($arr) !== range(0, count($arr) - 1);
        };

        if ($isAssoc($apiParams)) {
            // If the array is already nested (no dot notation keys), return as is
            $hasDot = false;
            foreach (array_keys($apiParams) as $k) {
                if (strpos($k, '.') !== false) {
                    $hasDot = true;
                    break;
                }
            }
            if (!$hasDot) {
                return $apiParams;
            }
            // Build nested structure from dot notation keys
            foreach ($apiParams as $paramName => $value) {
                $keys = explode('.', $paramName);
                $current = &$result;

                foreach ($keys as $key) {
                    // Handle array notation like [0]
                    if (preg_match('/^\[(\d+)\]$/', $key, $matches)) {
                        $key = (int) $matches[1];
                    }
                    if (!isset($current[$key])) {
                        $current[$key] = [];
                    }
                    $current = &$current[$key];
                }

                if (is_int($value)) {
                    $checkHist = FormAnswerUserDet::where('cfaud_batch', $batchID)
                        ->where('cfm_id', $cfmId)
                        ->where('cfmd_id', $value)
                        ->first();

                    if (!empty($checkHist)) {
                        $value = $checkHist->cfm_val;
                    }
                }

                $current = $value;
                unset($current);
            }
            return $result;
        }

        // Otherwise, treat as list of param objects (legacy)
        foreach ($apiParams as $param) {
            if (is_array($param)) {
                $paramName = $param['param_name'];
                $value = isset($param['form_id']) && !empty($param['form_id']) ? 'CMS_REPORT_' . $param['form_id'] :
                    ($param['default_value'] ?? null);
            } elseif (is_object($param)) {
                $paramName = $param->param_name;
                $value = isset($param->form_id) && !empty($param->form_id) ? 'CMS_REPORT_' . $param->form_id :
                    ($param->default_value ?? null);
            } else {
                continue;
            }

            $keys = explode('.', $paramName);
            $current = &$result;
            foreach ($keys as $key) {
                if (preg_match('/^\[(\d+)\]$/', $key, $matches)) {
                    $key = (int) $matches[1];
                }
                if (!isset($current[$key])) {
                    $current[$key] = [];
                }
                $current = &$current[$key];
            }
            $current = $value;
            unset($current);
        }

        return $result;
    }

    public function getConnectedMRS($id)
    {
        return MRSReportMstr::where(DB::raw('CAST(mrm_query AS NVARCHAR(MAX))'), (string) $id)->first() ?: [];
    }

    public function getSetupFormsForForm($id)
    {
        $setupTraining = $this->getDataGencode(
            'FORMS_SETUP',
            [
                'pgm_value' => $id
            ],
            [
                'pgm_desc' => 'pgm_value2|string'
            ],
            [],
            true,
            false,
            false
        );

        // [
        //         'idDomain' => 'pgm_value|string',
        //         'stateCMS' => 'pgm_value2|string',
        //         'urlCMS' => 'pgm_desc3|string',
        //     ]

        // return $setupTraining;

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
                    $setupTrainingRes[$k] = (bool) $v;
                }
            }
        }

        return $setupTrainingRes;
    }

    public function downloadTemplateBulk($id)
    {
        $dataMRS = $this->getConnectedMRS($id);

        $cekReport = MRSReportMstr::select(
            'mrs_report_mstr.*'
        )
            ->where('mrs_report_mstr.id', $dataMRS->id)
            ->first();

        // return $cekReport->id;

        $filename = 'export_' . $cekReport->mrm_name . '_upload_template_' . date('ymd_his') . '.xlsx';

        Excel::store(new ExportReport([], $dataMRS->id, true), 'MRS/' . $filename, 'public');

        return [
            'status' => true,
            'path' => '/storage/MRS/' . $filename,
        ];
    }
}
