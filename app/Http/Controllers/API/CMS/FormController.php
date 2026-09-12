<?php

namespace App\Http\Controllers\API\CMS;

use App\Http\Controllers\API\PORTAL\BaseController as BaseController;
use App\Models\CMS\FormAnswerUserDet;
use App\Models\CMS\FormLogicsDet;
use App\Models\PORTAL\PortalApp;
use App\Models\PORTAL\PortalRoleAppMap;
use App\Models\PORTAL\PortalRoleUserMap;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Excel;

use App\Models\CMS\FormMaster;
use App\Models\CMS\FormMultiDet;
use App\Models\CMS\FormAnswerDet;
use App\Models\CMS\FormMasterTitle;
use App\Models\CMS\FormSetupDet;
use App\Models\CMS\FormShareDet;
use App\Models\CMS\FormAMSMapDet;
use App\Models\PORTAL\PortalNotif;
use App\Models\PORTAL\PortalGencode;
use App\Models\MRS\MRSReportMstr;


use App\Http\Requests\MRS\ReportCreateRequest;
use App\Traits\CMS\FormsTraits;
use App\Traits\AMS\ApprovalActionTraits;
use App\Http\Requests\AMS\ApprovalRunningApproveActionRequest;
use App\imports\CMS\importBulkAnswers;

class FormController extends BaseController
{
    use FormsTraits, ApprovalActionTraits;
    public function __construct()
    {
        // Increase script execution time for heavy queries
        set_time_limit(1800); // 30 minutes, adjust as needed
        ini_set('max_execution_time', 1800);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->forms;

        $insertMaster = FormMasterTitle::updateOrCreate([
            'id' => $request->idRef
        ], [
            'p_u_username' => $request->header('username'),
            'cfmt_title' => $request->title,
            'cfmt_quiz_flag' => (int) $request->isQuiz,
            'cfmt_status' => $request->input('status', 'draft'),
            'cfmt_year' => $request->input('year', null),
        ]);

        // This is for Frontpage posts and pages
        if ($request->isQuiz == 2 || $request->isQuiz == 3) {
            PortalGencode::updateOrCreate([
                'pgm_code' => 'URL_PAGE_GEN',
                'pgm_value' => $insertMaster->id,
            ], [
                'pgm_code' => 'URL_PAGE_GEN',
                'pgm_value' => $insertMaster->id,
                'pgm_desc' => Str::slug($request->title),
                'pgm_desc2' => $request->desc ?? '',
                'pgm_created_by' => $request->header('username'),
            ]);
        }

        if (isset($request->idRef) && !empty($request->idRef)) {
            // FormMaster::where('cfmt_id', $request->idRef)->delete();
        }

        if (isset($request->setupTraining) && !empty($request->idRef)) {
            // FormSetupDet::where('cfmt_id', $request->idRef)->delete();
            $cekSetup = FormSetupDet::where('cfmt_id', $request->idRef)->first();

            if ($request->isQuiz == 1 && !empty($request->setupTraining)) {
                FormSetupDet::updateOrCreate([
                    'cfmt_id' => $request->idRef,
                ], [
                    'cfmt_id' => $request->idRef,
                    'cfsd_res_show' => $request->setupTraining['showResult'],
                    'cfsd_ans_show' => $request->setupTraining['showRightKeysAnswer'],
                    'cfsd_rand_quest' => $request->setupTraining['randomizeQuestion'],
                    'cfsd_ans_loc' => $request->setupTraining['showRightKeysAnswerLocation'],
                    'cfsd_timer' => $request->setupTraining['setUpTimer'],
                    'cfsd_timer_quest' => $request->setupTraining['timerEveryQuestion'],
                    'cfsd_hours' => $request->setupTraining['hourTimer'],
                    'cfsd_min' => $request->setupTraining['minTimer'],
                    'cfsd_sec' => $request->setupTraining['secTimer'],
                    'cfsd_min_pass' => $request->setupTraining['minPass'],
                    'cfsd_start_quiz' => $request->setupTraining['startQuiz'],
                    'cfsd_end_quiz' => $request->setupTraining['endQuiz'],
                    'cfsd_real_start_quiz' => empty($cekSetup) ? $request->setupTraining['startQuiz'] : $cekSetup->cfsd_real_start_quiz,
                    'cfsd_real_end_quiz' => empty($cekSetup) ? $request->setupTraining['endQuiz'] : $cekSetup->cfsd_real_end_quiz,
                    'cfsd_quest_limit' => $request->setupTraining['maxQuestionCount'],
                    'cfsd_skip_next_btn_media_done' => $request->setupTraining['maxQuestionCount']
                ]);

                if ($request->isQuiz == 2) {
                    PortalGencode::updateOrCreate([
                        'pgm_code' => 'URL_PAGE_GEN',
                        'pgm_value' => $request->idRef,
                    ], [
                        'pgm_code' => 'URL_PAGE_GEN',
                        'pgm_value' => $request->idRef,
                        'pgm_desc' => Str::slug($request->title),
                        'pgm_created_by' => $request->header('username'),
                    ]);
                }
            } else {
                foreach ($request->setupTraining as $key => $valueSetup) {
                    PortalGencode::updateOrCreate([
                        'pgm_code' => 'FORMS_SETUP',
                        'pgm_value' => $request->idRef,
                        'pgm_desc' => $key,
                    ], [
                        'pgm_code' => 'FORMS_SETUP',
                        'pgm_value' => $request->idRef,
                        'pgm_value2' => is_array($valueSetup) ? json_encode($valueSetup) : $valueSetup,
                        'pgm_desc' => $key,
                        'pgm_desc2' => $request->desc ?? '',
                        'pgm_created_by' => $request->header('username'),
                    ]);

                    if ($key === 'isHistory' && $valueSetup == true) {
                        // If isHistory is true, then we need to create a new table for history
                        $cekDefID = PortalGencode::where('pgm_code', 'MRS_DB_MSTR_DEF_ID')
                            ->first();

                        $getReport = MRSReportMstr::where('mrm_url_gen', 'cms')
                            // ->where('mrm_name', $request->title . ' History')
                            ->whereRaw("CAST(mrm_query AS NVARCHAR(MAX)) = CAST(? AS NVARCHAR(MAX))", [$request->idRef])
                            ->where('mrm_url_gen', 'cms')
                            ->first();

                        $checkSetup = $this->getSetupFormsForForm($request->idRef);
                        // return $checkSetup;
                        if (($checkSetup['isRPA'] ?? 0) == 1) {
                            $descUrl = 'rpa';
                        } else {
                            $descUrl = 'cms';
                        }

                        if (($checkSetup['isApproval'] ?? 0) == 1) {
                            $descUrl .= '|approval';
                        }

                        $header = [
                            'p_u_username' => $request->header('username'),
                            'mdm_id' => $cekDefID->pgm_value,
                            'mrm_name' => $request->title . ' History',
                            'mrm_db' => 'STX_CMS',
                            'mrm_table' => 'cms_form_ans_user_det',
                            'mrm_query' => strval($request->idRef),
                            'mrm_url_gen' => $descUrl,
                        ];

                        if ($getReport) {
                            $header['id'] = $getReport->id;
                        }

                        $getDataForDet = $this->showHistory(new Request([
                            'histTableList' => $request->setupTraining['historyTableList']
                        ]), $request->idRef);
                        // Decode the response content to access data
                        $responseData = json_decode($getDataForDet->getContent(), true);

                        $det = [];
                        if (isset($responseData['data']['columns'])) {
                            foreach ($request->setupTraining['historyTableList'] as $keyCol => $valueCol) {
                                $det[] = [
                                    'field' => $valueCol['name'],
                                    'label' => $valueCol['label'],
                                    'active' => $valueCol['isVisible'],
                                    'sortable' => $valueCol['isSortable'],
                                    'filterable' => $valueCol['isFiltered'],
                                    'exported' => $valueCol['isExportable'] ?? false,
                                    'type' => 'text',
                                ];
                            }
                        }

                        app('App\Http\Controllers\API\MRS\ReportController')->directStore(new Request(['header' => $header, 'det' => $det]));
                    }
                }
            }
        }

        // Setup Share forms - fixed: support role IDs, allow main-menu without shareForms, and create role mappings - keep link stable
        $isMainMenu = !empty($request->shareFormsIsMainMenu) && $request->shareFormsIsMainMenu;
        $existingLink = null;
        if (!empty($request->idRef)) {
            $existingLink = FormShareDet::where('cfmt_id', $request->idRef)->value('cfsd_gen_link');
            if (!$existingLink) {
                $existingApp = PortalApp::where('am_app_code', 'FRM-' . $request->idRef)->first();
                if ($existingApp && preg_match('/linkID=([A-Za-z0-9]+)/', $existingApp->am_app_url, $m)) $existingLink = $m[1];
            }
        }
        $randomString = $existingLink ?: Str::random(30);
        $roleIdsForMenu = [];
        if (isset($request->shareFormsRoleID) && is_array($request->shareFormsRoleID) && count($request->shareFormsRoleID) > 0) {
            $roleIdsForMenu = array_values(array_unique(array_filter($request->shareFormsRoleID)));
        } elseif ($isMainMenu && !empty($request->shareFormsIsRoles) && $request->shareFormsIsRoles && isset($request->shareForms) && is_array($request->shareForms)) {
            foreach ($request->shareForms as $u) {
                $map = PortalRoleUserMap::where('u_username', $u)->first();
                if ($map && !in_array($map->rm_role_id, $roleIdsForMenu)) $roleIdsForMenu[] = $map->rm_role_id;
            }
        }

        if (isset($request->shareForms) && !empty($request->idRef) && is_array($request->shareForms) && count($request->shareForms) > 0) {
            foreach ($request->shareForms as $keyShare => $valueShare) {
                $cekIDRoles = PortalRoleUserMap::where('u_username', $valueShare)->first();
                $roleIdForShare = '';
                if (!empty($request->shareFormsIsRoles) && $request->shareFormsIsRoles) {
                    if (!empty($roleIdsForMenu)) {
                        $roleIdForShare = $roleIdsForMenu[0];
                    } else {
                        $roleIdForShare = $cekIDRoles ? $cekIDRoles->rm_role_id : '';
                    }
                }
                FormShareDet::updateOrCreate([
                    'cfmt_id' => $request->idRef,
                    'cfsd_to' => $valueShare,
                ], [
                    'cfmt_id' => $request->idRef,
                    'p_u_username' => $request->header('username'),
                    'cfsd_to' => $valueShare,
                    'cfsd_gen_link' => $randomString,
                    'cfsd_role_id' => $roleIdForShare,
                    'cfsd_is_menu' => $isMainMenu ? 1 : 0
                ]);

                if (!$isMainMenu) {
                    PortalNotif::create([
                        'p_u_username' => $request->header('username'),
                        'pnm_to_users' => $valueShare,
                        'pnm_title' => $request->isQuiz == true ? 'Training / Quiz' : 'Important Notice',
                        'pnm_content' => $request->isQuiz == true
                            ? 'You have a new Training / Quiz : <b>' . $request->title . '</b>, please do it before expired !'
                            : 'You have new information about <b>' . $request->title . '</b>',
                        'pnm_action_url' => $request->isQuiz == true
                            ? 'TOS/Quiz/showLiveForms'
                            : 'TOS/Quiz/showLiveForms',
                        'pnm_hash_id_location' => $randomString,
                        'pnm_start_date' => $request->has('setupTraining') ? $request->setupTraining['startQuiz'] : date('Y-m-d'),
                        'pnm_end_date' => $request->has('setupTraining') ? $request->setupTraining['endQuiz'] : NULL
                    ]);
                }
            }
        }

        if ($isMainMenu && !empty($request->idRef)) {
            $appInsert = PortalApp::updateOrCreate([
                'am_app_code' => 'FRM-' . $request->idRef,
            ], [
                'u_username' => $request->header('username'),
                'am_app_code' => 'FRM-' . $request->idRef,
                'am_app_name' => $request->title,
                'am_app_desc' => $request->title,
                'am_app_url' => 'CMS/formsAsApps?linkID=' . $randomString,
                'am_app_icon' => $request->shareFormsMenuIcon,
                'am_app_parent' => $request->selectedSharedMenu,
                'am_local_form' => 1
            ]);

            foreach ($roleIdsForMenu as $rid) {
                if (!empty($request->selectedSharedMenu)) {
                    PortalRoleAppMap::updateOrCreate([
                        'rm_role_id' => $rid,
                        'am_app_id' => $request->selectedSharedMenu,
                    ], [
                        'u_username' => $request->header('username'),
                        'rm_role_id' => $rid,
                        'am_app_id' => $request->selectedSharedMenu,
                        'am_app_parent' => null
                    ]);
                }
                PortalRoleAppMap::updateOrCreate([
                    'rm_role_id' => $rid,
                    'am_app_id' => $appInsert->am_app_code,
                ], [
                    'u_username' => $request->header('username'),
                    'rm_role_id' => $rid,
                    'am_app_id' => $appInsert->am_app_code,
                    'am_app_parent' => $request->selectedSharedMenu
                ]);
            }
            if (!FormShareDet::where('cfsd_gen_link', $randomString)->exists()) {
                FormShareDet::updateOrCreate([
                    'cfmt_id' => $request->idRef,
                    'cfsd_to' => $request->header('username'),
                ], [
                    'cfmt_id' => $request->idRef,
                    'p_u_username' => $request->header('username'),
                    'cfsd_to' => $request->header('username'),
                    'cfsd_gen_link' => $randomString,
                    'cfsd_role_id' => $roleIdsForMenu[0] ?? '',
                    'cfsd_is_menu' => 1
                ]);
            }
        }

        if (!empty($request->idRef)) {
            // Collect ids that correspond to REAL FormMaster rows for this page:
            // top-level items, plus children of 'row' (the only type that
            // stores children as separate rows). Nested children of
            // columns/carousel live inside the parent's JSON content, so they
            // must NOT be treated as rows — otherwise a stale nested id can
            // keep a deleted top-level row alive.
            $formsForExtract = json_decode(json_encode($request->forms), true);

            $getListUpdatedID = [];
            $collectRowIds = function ($items) use (&$getListUpdatedID, &$collectRowIds) {
                if (!is_array($items)) {
                    return;
                }
                foreach ($items as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    if (isset($item['id']) && is_numeric($item['id'])) {
                        $getListUpdatedID[] = (int) $item['id'];
                    }
                    if (($item['type'] ?? '') === 'row' && isset($item['content']) && is_array($item['content'])) {
                        $collectRowIds($item['content']);
                    }
                }
            };
            $collectRowIds($formsForExtract);

            if (count($getListUpdatedID) > 0) {
                FormMaster::where('cfmt_id', $insertMaster->id)
                    ->whereNotIn('id', $getListUpdatedID)
                    ->delete();
                FormAnswerDet::where('cfm_id', $insertMaster->id)
                    ->whereNotIn('cfmd_id', $getListUpdatedID)
                    ->delete();

                FormLogicsDet::where('cfm_id', $insertMaster->id)
                    ->whereNotIn('id', $getListUpdatedID)
                    ->delete();
            }
        }

        $hasil = [];
        $listPage = [];

        foreach ($data as $key => $value) {
            // $listPage[] = $value['seq_name'];
            if (empty($value['seq_name'])) {
                $value['seq_name'] = $key + 1;
            }

            $hasil[] = $this->storingForms(
                $value,
                $request->header('username'),
                isset($request->ans) ? $request->ans : [],
                isset($request->exp) ? $request->exp : [],
                $insertMaster->id,
                0,
                $key,
            );

            $listValForSubscribers = [];
            if ($request->has('tags') && !empty($request->tags)) {
                app('App\Http\Controllers\API\PORTAL\FrontPageController')->saveTags(new Request([
                    'id' => $insertMaster->id,
                    'tags' => $request->tags,
                    'username' => $request->header('username'),
                ]));

                foreach ($request->tags as $key => $valueCat) {
                    $listValForSubscribers[] = ['type' => 'categories', 'value' => $valueCat, 'user_id' => $request->header('username')];
                }
            }

            if ($request->has('hashtags') && !empty($request->hashtags)) {
                app('App\Http\Controllers\API\PORTAL\FrontPageController')->saveHashTags(new Request([
                    'id' => $insertMaster->id,
                    'hashtags' => $request->hashtags,
                    'username' => $request->header('username'),
                ]));

                foreach ($request->tags as $key => $valueCat) {
                    $listValForSubscribers[] = ['type' => 'tags', 'value' => $valueCat, 'user_id' => $request->header('username')];
                }
            }

            $listValForSubscribers[] = ['type' => 'users', 'value' => $request->header('username'), 'user_id' => $request->header('username')];
        }

        return $this->handleResponse([
            'insert' => $hasil,
            'id' => $insertMaster->id,
        ], 'Data Found !');
        // return response($hasil);
    }

    public function storeAnswers(Request $request)
    {
        $formTitle = FormMasterTitle::find($request->id);
        $username = $request->has('username') ? $request->username : $request->header('username');
        $status = $formTitle->cfmt_status ?? null;

        $setup = $this->getSetupFormsForForm($request->id);
        $listOn = in_array(
            $setup['specificUserSetViewHistory'] ?? null,
            [true, 1, '1', 'true'],
            true
        );

        $memberBypass = false;
        if ($listOn) {
            $list = is_array($setup['listSpecificUserRoleSetViewHistory'] ?? null)
                ? $setup['listSpecificUserRoleSetViewHistory']
                : [];

            if (($setup['userView'] ?? 'user') === 'role') {
                $roleId = $request->header('roleid');
                $memberBypass = collect($list)->contains(function ($r) use ($roleId) {
                    return (string) $r === (string) $roleId;
                });
            } else {
                $memberBypass = collect($list)->contains(function ($u) use ($username) {
                    return strtolower(trim((string) $u)) === strtolower(trim((string) $username));
                });
            }
        }

        if ($formTitle && ($status === 'draft' || $status === 'closed') && !$memberBypass) {
            return $this->handleError('This form is ' . ($status ?: 'draft') . ' and not accepting responses.', []);
        }

        if (!$memberBypass) {
            $connectedMRS = $this->getConnectedMRS((string) $request->id);
            if (!empty($connectedMRS) && !empty($connectedMRS->id)) {
                $periodRow = PortalGencode::where('pgm_code', 'MRS_FORM_PERIOD')
                    ->whereRaw("CAST(pgm_value AS varchar(max)) = ?", [(string) $connectedMRS->id])
                    ->whereRaw("CAST(pgm_value2 AS varchar(max)) = ?", [(string) $username])
                    ->first();
                if (!empty($periodRow) && !empty($periodRow->pgm_value3)) {
                    $period = json_decode($periodRow->pgm_value3, true);
                    if (!empty($period['from']) && !empty($period['to'])) {
                        $now = now()->format('Y-m-d H:i');
                        $toBound = strlen($period['to']) <= 10 ? $period['to'] . ' 23:59:59' : $period['to'];
                        if ($now < $period['from'] || $now > $toBound) {
                            return $this->handleError('This form is outside the active period.', []);
                        }
                    }
                }
            }
        }

        if ($formTitle && $formTitle->cfmt_quiz_flag !== 1) {
            $checkSetup = $this->getSetupFormsForForm($request->id);
            $addPeriod = isset($checkSetup['addPeriod']) && ($checkSetup['addPeriod'] === true || $checkSetup['addPeriod'] == 1 || $checkSetup['addPeriod'] === '1');

            if ($addPeriod) {
                $now = now()->format('Y-m-d H:i');
                $start = $checkSetup['startQuiz'] ?? null;
                $end = $checkSetup['endQuiz'] ?? null;

                if (!empty($start) && $now < $start) {
                    return $this->handleError('This form is not yet available.', []);
                }
                if (!empty($end) && $now > $end) {
                    return $this->handleError('This form is no longer available. The deadline has passed.', []);
                }
            }
        }

        $getID = FormAnswerUserDet::where('cfm_id', $request->id)
            ->where('p_u_username', $request->has('username') ? $request->username : $request->header('username'))
            ->orderBy('created_at', 'desc')
            ->first();

        $needStores = true;

        if (empty($getID)) {
            $getDeletedID = FormAnswerUserDet::withTrashed()->where('cfm_id', $request->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $nextID = empty($getDeletedID) ? 1 : $getDeletedID['cfaud_batch'] + 1;
        } else {
            $nextID = $getID['cfaud_batch'] + 1;
        }

        if ($request->has('batch_id') && !empty($request->batch_id)) {
            $nextID = $request->batch_id;
        }

        $checkSetup = $this->getSetupFormsForForm($request->id);
        $ans = $request->ans;
        if (!is_array($ans)) {
            $ans = [];
        }

        // Build canonical → live ID mapping from historyTableList (by label)
        $canonicalToLive = [];
        if (isset($checkSetup['historyTableList']) && is_array($checkSetup['historyTableList'])) {
            $liveFormFields = FormMaster::where('cfmt_id', $request->id)
                ->where('cfm_type', 'form')
                ->get();
            foreach ($checkSetup['historyTableList'] as $histField) {
                if (!isset($histField['forms']['id'])) continue;
                $canonicalId = $histField['forms']['id'];
                $canonicalLabel = trim($histField['forms']['content']['label'] ?? $histField['label'] ?? '');
                if (!$canonicalLabel) continue;

                // Find matching live field by label
                $liveField = $liveFormFields->first(function ($field) use ($canonicalLabel) {
                    $content = json_decode($field->cfm_content, true);
                    $liveLabel = trim($content['label'] ?? '');
                    return $liveLabel === $canonicalLabel;
                });

                if ($liveField) {
                    $canonicalToLive[$canonicalId] = $liveField->id;
                }
            }
        }

        // Collect all filled field IDs (accept both canonical and live IDs for validation)
        $allFilledIds = [];
        foreach ($ans as $rowAnswers) {
            if (is_array($rowAnswers)) {
                foreach ($rowAnswers as $fieldId => $value) {
                    $allFilledIds[] = $fieldId; // canonical ID from frontend
                    // Also add corresponding live ID if mapped
                    if (isset($canonicalToLive[$fieldId])) {
                        $allFilledIds[] = $canonicalToLive[$fieldId];
                    }
                }
            }
        }

        $cekForm = FormMaster::where('cfmt_id', $request->id)->where('cfm_type', 'form')->where('cfm_required', 1)->get()->toArray();
        $hasilError = [];
        foreach ($cekForm as $valueCekForms) {
            if (!in_array($valueCekForms['id'], $allFilledIds)) {
                $getContent = json_decode($valueCekForms['cfm_content']);
                $hasilError[$valueCekForms['id']] = [$getContent->label . ' is required'];
            }
        }

        if (count($hasilError) > 0) {
            return $this->handleError('There is required field not filled yet !', $hasilError);
        }

        // Flatten first row for API param lookup (backward compatible)
        $spreadAnswer = [];
        foreach ($ans as $rowAnswers) {
            if (is_array($rowAnswers)) {
                foreach ($rowAnswers as $fieldId => $value) {
                    $spreadAnswer[$fieldId] = $value;
                }
            }
        }

        $bulkMode = $checkSetup['bulkMode'] ?? 'once';
        $isBulk = is_array($ans) && count($ans) > 1;

        // RPA - bulk aware
        if (($checkSetup['isRPA'] ?? 0) == 1) {
            if ($isBulk && $bulkMode === 'skip') {
                // skip RPA for bulk
            } elseif ($isBulk && $bulkMode === 'perRow') {
                foreach ($ans as $rowIdx => $rowAns) {
                    $rowBatchId = $nextID + $rowIdx;
                    $params = $this->buildNestedParams($checkSetup['rpaParams'] ?? [], $request->id, $rowBatchId);
                    app('App\Http\Controllers\API\RPA\RPAHistController')->store(new Request([
                        'prh_prmid' => ($checkSetup['rpaId'] ?? [])['id'] ?? null,
                        'prh_robotnm' => ($checkSetup['rpaId'] ?? [])['prm_name'] ?? null,
                        'prh_command' => json_encode($params),
                        'prh_flag' => 0,
                        'prh_result' => 'Starting RPA',
                        'prh_cfaud_id' => $request->id,
                        'prh_cfaud_batch_id' => $rowBatchId,
                    ]));
                }
            } else {
                $getRPAId = $checkSetup['rpaId'] ?? [];
                $params = $this->buildNestedParams($checkSetup['rpaParams'] ?? [], $request->id, $nextID);
                app('App\Http\Controllers\API\RPA\RPAHistController')->store(new Request([
                    'prh_prmid' => $getRPAId['id'] ?? null,
                    'prh_robotnm' => $getRPAId['prm_name'] ?? null,
                    'prh_command' => json_encode($params),
                    'prh_flag' => 0,
                    'prh_result' => 'Starting RPA',
                    'prh_cfaud_id' => $request->id,
                    'prh_cfaud_batch_id' => $nextID,
                ]));
            }
        }

        if (($checkSetup['isApproval'] ?? 0) == 1) {
            if ($isBulk && $bulkMode === 'skip') {
                // skip approval for bulk
            } elseif ($isBulk && $bulkMode === 'perRow') {
                foreach ($ans as $rowIdx => $rowAns) {
                    $this->sendApproval(new Request([
                        'idRef' => $request->id,
                        'username' => $request->has('username') ? $request->username : $request->header('username'),
                        'batch_id' => $nextID + $rowIdx,
                    ]));
                }
            } else {
                $this->sendApproval(new Request([
                    'idRef' => $request->id,
                    'username' => $request->has('username') ? $request->username : $request->header('username'),
                ]));
            }
        }

        if (isset($checkSetup['isNotif']) && $checkSetup['isNotif'] == 1) {
            if ($isBulk && $bulkMode === 'skip') {
                // skip notif for bulk
            } elseif ($isBulk && $bulkMode === 'perRow') {
                foreach ($ans as $rowIdx => $rowAns) {
                    $this->sendNotifFormsSubmitted(new Request([
                        'idRef' => $request->id,
                        'username' => $request->has('username') ? $request->username : $request->header('username'),
                        'batch_id' => $nextID + $rowIdx,
                    ]));
                }
            } else {
                $this->sendNotifFormsSubmitted(new Request([
                    'idRef' => $request->id,
                    'username' => $request->has('username') ? $request->username : $request->header('username'),
                ]));
            }
        }

        $hasilAPICall = [];
        if (($checkSetup['isAPI'] ?? 0) == 1 && !empty($checkSetup['apiOpt'])) {
            if ($isBulk && $bulkMode === 'skip') {
                // skip API for bulk
            } elseif ($isBulk && $bulkMode === 'perRow') {
                foreach ($ans as $rowIdx => $rowAns) {
                    foreach ($checkSetup['apiOpt'] as $keyApi => $valueApi) {
                        $buildParams = [];
                        foreach ($valueApi['params'] as $keyParam => $valueParam) {
                            $formValue = $rowAns[$valueParam['form_id']] ?? $valueParam['param_default'] ?? null;
                            $buildParams[$valueParam['param_name']] = $formValue;
                        }
                        $hasilAPICall[] = $this->sendAPIFormsSubmitted(new Request([
                            'idRef' => $request->id,
                            'username' => $request->has('username') ? $request->username : $request->header('username'),
                            'apiUrl' => $valueApi['apiUrl'],
                            'method' => $valueApi['method'],
                            'headers' => $valueApi['headers'],
                            'isDownload' => isset($valueApi['isDownload']) ? $valueApi['isDownload'] : false,
                            'params' => $buildParams,
                        ]));
                    }
                }
            } else {
                foreach ($checkSetup['apiOpt'] as $keyApi => $valueApi) {
                    $buildParams = [];
                    foreach ($valueApi['params'] as $keyParam => $valueParam) {
                        $formValue = $spreadAnswer[$valueParam['form_id']] ?? $valueParam['param_default'] ?? null;
                        $buildParams[$valueParam['param_name']] = $formValue;
                    }
                    $hasilAPICall[] = $this->sendAPIFormsSubmitted(new Request([
                        'idRef' => $request->id,
                        'username' => $request->has('username') ? $request->username : $request->header('username'),
                        'apiUrl' => $valueApi['apiUrl'],
                        'method' => $valueApi['method'],
                        'headers' => $valueApi['headers'],
                        'isDownload' => isset($valueApi['isDownload']) ? $valueApi['isDownload'] : false,
                        'params' => $buildParams,
                    ]));
                }
            }

            $apiCallsList = array_map(function ($index) use ($hasilAPICall, $checkSetup) {
                $item = $hasilAPICall[$index];
                $data = $item;
                if ($item instanceof \Illuminate\Http\JsonResponse) {
                    $data = json_decode($item->getContent(), true);
                }
                if (is_array($item)) {
                    $data = $item['original'] ?? $item;
                }

                return array_merge($data, [
                    'opt' => $checkSetup['apiOpt'][$index]
                ]);
            }, array_keys($hasilAPICall));

            if (
                count(array_filter($apiCallsList, function ($item) {
                    return isset($item['status']) && $item['status'] === true;
                })) !== count($apiCallsList)
            ) {
                return $this->handleError('One or more API calls failed during form submission, cancel the operation.', [
                    'apiCalls' => $apiCallsList
                ]);
            }
        }

        // Store answers — batch strategy depends on renderMode:
        // - "multiple": each row = separate instance → separate batch (nextID + rowIdx)
        // - "disabled" / wizard: all rows = same submission → single batch (nextID)
        $renderMode = $checkSetup['renderMode'] ?? 'disabled';
        $isMultipleMode = $renderMode === 'multiple';

        $hasil = [];
        foreach ($ans as $rowIdx => $rowAnswers) {
            if (!is_array($rowAnswers)) continue;

            $rowBatchId = $isMultipleMode ? ($nextID + $rowIdx) : $nextID;

            foreach ($rowAnswers as $fieldId => $value) {
                if ($fieldId === 'undefined' || $fieldId === 'null' || $fieldId === '' || $fieldId === null) {
                    continue;
                }
                if (!is_numeric($fieldId)) {
                    continue;
                }
                $result = FormAnswerUserDet::updateOrCreate([
                    'p_u_username' => $request->has('username') ? $request->username : $request->header('username'),
                    'cfaud_batch' => $rowBatchId,
                    'cfm_id' => $request->id,
                    'cfmd_id' => $fieldId,
                ], [
                    'p_u_username' => $request->has('username') ? $request->username : $request->header('username'),
                    'cfaud_batch' => $rowBatchId,
                    'cfm_id' => $request->id,
                    'cfmd_id' => $fieldId,
                    'cfm_val' => (string) $value,
                ])->toArray();

                $hasil[] = $result;
            }
        }

        if (count($hasil) === 0 && count($ans) > 0) {
            return $this->handleError('No valid answers to store (invalid field IDs).', $ans);
        }

        return $this->handleResponse(($checkSetup['isAPI'] ?? 0) == 1 && !empty($checkSetup['apiOpt']) ? $apiCallsList : $hasil, 'Form submited !');
    }

    public function storeBulkAnswers(Request $request)
    {
        $request->validate([
            'files' => 'required|string',
        ]);

        $base64File = $request->input('files');
        if (strpos($base64File, ',') !== false) {
            list($type, $base64File) = explode(',', $base64File, 2);
        }

        // Decode base64 string and create temporary file
        $fileContent = base64_decode($base64File, true);
        if ($fileContent === false) {
            return $this->handleError('Invalid base64 file format', []);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'excel_');
        file_put_contents($tempPath, $fileContent);

        $dataMRS = $this->getConnectedMRS($request->id);

        $cekReport = MRSReportMstr::select(
            'mrs_report_mstr.*'
        )
            ->where('mrs_report_mstr.id', $dataMRS->id)
            ->first();

        $checkSetup = $this->getSetupFormsForForm($request->id);
        $importer = new importBulkAnswers($request->header('username'), $cekReport->id, $request->id, $checkSetup);
        Excel::import($importer, $tempPath);

        // Clean up temporary file
        unlink($tempPath);

        return $this->handleResponse($importer, 'Bulk Form submited !');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, $tags = '', $showMax = 0, $orderBy = [], $isPublisedOnly = false, $isPaginated = false, $page = 1, $users = '', $filter = [])
    {
        // return [$id, $tags, $showMax, $orderBy, $isPublisedOnly, $isPaginated, $page];
        $dataBuild = formMasterTitle::with(['quizSetup', 'shared']);
        $normalizeFilterValue = function ($filterValue) {
            if (!is_string($filterValue)) {
                return $filterValue;
            }

            $trimmed = trim($filterValue);
            if ($trimmed === '') {
                return $filterValue;
            }

            // Keep SQL wildcard searches intact (e.g. 2026-07-09%)
            $tryDate = str_replace('%', '', $trimmed);
            if ($tryDate === '') {
                return $filterValue;
            }

            $timestamp = strtotime($tryDate);
            if ($timestamp === false) {
                return $filterValue;
            }

            return date('Y-m-d', $timestamp);
        };

        if (!empty($users)) {
            $dataBuild->where('cms_form_mstr_title.p_u_username', $users);
        }

        if ($id === 'quiz') {
            $data = (clone $dataBuild)->select('cms_form_mstr_title.*')->with([
                'formMaster' => function ($f) {
                    $f->where('cfm_parent_id', 0);
                    $f->with('formDetail.formAnswer');
                    $f->with('allChildrenContent.formDetail.formAnswer');
                    $f->orderBy('cfm_seq_name', 'asc');
                }
            ])->where('cfmt_quiz_flag', 1)
                ->get();
        } elseif ($id === 'page' || $id === 'post') {
            $dataBuild->select('cms_form_mstr_title.*', 'pgTags.pgm_value2 as tags')->where('cfmt_quiz_flag', $id === 'page' ? 2 : 3);

            $dataBuild->leftjoin(DB::raw('STX_PORTAL.dbo.portal_gencode_mstr as pgTags'), function ($join) {
                $join->on(DB::raw('STX_CMS.dbo.cms_form_mstr_title.id'), '=', 'pgTags.pgm_value')
                    ->where('pgTags.pgm_code', 'FP_TAGS_LIST')
                    ->whereNotNull('pgTags.pgm_value2');
            });

            if (count($orderBy) > 0) {
                foreach ($orderBy as $order) {
                    foreach ($order as $field => $direction) {
                        // Remove quotes from field and direction if they exist
                        $cleanField = trim($field, '"');
                        $cleanDirection = trim($direction, '"');
                        $dataBuild->orderBy(DB::raw('cms_form_mstr_title.' . $cleanField), $cleanDirection);
                    }
                }
            }

            if ($isPublisedOnly) {
                $data = $dataBuild->join(DB::raw('STX_PORTAL.dbo.portal_gencode_mstr as pg'), function ($join) {
                    $join->on(DB::raw('STX_CMS.dbo.cms_form_mstr_title.id'), '=', 'pg.pgm_value')
                        ->where('pg.pgm_code', 'FP_PUBLISH_POSTS')
                        ->whereNotNull('pg.pgm_value2');
                });
            }

            if (!empty($tags)) {
                $decodedTagsArray = json_decode(base64_decode($tags), true);
                if (!is_array($decodedTagsArray)) {
                    $decodedTagsArray = [];
                }

                // Keep only real tag values. "all", empty, or blank entries mean
                // "no tag filter" — otherwise whereIn([...]) with no values
                // would match nothing and the feed would always be empty.
                $decodedTagsArray = array_values(array_filter($decodedTagsArray, function ($tag) {
                    return $tag !== null && $tag !== '' && $tag !== 'all';
                }));

                if (count($decodedTagsArray) > 0) {
                    $dataBuild->whereIn('pgTags.pgm_value2', $decodedTagsArray);
                }
            }

            if (count($filter) > 0) {
                foreach ($filter as $value) {
                    $column = $value['cols'] ?? $value['field'] ?? null;
                    $operator = $value['param'] ?? $value['params'] ?? '=';
                    $filterValue = $value['value'] ?? null;

                    if (!$column || $filterValue === null) {
                        continue;
                    }

                    if ($column === 'created_at' || $column === 'updated_at') {
                        $column = 'cms_form_mstr_title.' . $column;
                        $filterValue = $normalizeFilterValue($filterValue);
                        $dataBuild->whereBetween($column, [$filterValue . ' 00:00:00', $filterValue . ' 23:59:59']);
                    } else {
                        $filterValue = $normalizeFilterValue($filterValue);
                        $dataBuild->where($column, $operator, $filterValue);
                    }
                }
            }

            if ($isPaginated && $showMax > 0) {
                $data = $dataBuild->paginate((int) $showMax, ['*'], 'page', $page);
            } else {
                if ($showMax > 0 && !$isPaginated) {
                    $dataBuild->limit($showMax);
                }
                $data = $dataBuild->get();
            }

            $hasil = [];
            $items = $data;

            $hasil = $items->map(function ($value) use ($id, $tags, $isPublisedOnly, $orderBy, $isPaginated) {
                $getDataGencode = $this->getDataGencode(
                    'URL_PAGE_GEN',
                    ['pgm_value' => $value['id']],
                    [
                        'url' => 'pgm_desc|string',
                        'desc' => 'pgm_desc2|string',
                        'is_main' => 'pgm_value2|string',
                    ],
                    [],
                    true,
                    false
                );

                // return $value;

                $getPublished = $this->getDataGencode(
                    'FP_PUBLISH_POSTS',
                    ['pgm_value' => $value['id']],
                    [
                        'is_published' => 'pgm_value2|date',
                    ],
                    [],
                    true,
                    false
                );

                $getCategoriesUsers = $this->getDataGencode(
                    'FP_CATEGORY_USERS_POSTS',
                    ['pgm_value' => $value['p_u_username']],
                    [
                        'users_posts' => 'pgm_value|string',
                        'note' => 'pgm_desc|string',
                    ],
                    [],
                    true,
                    false
                );

                $getTagsData = $this->getDataGencode(
                    'FP_TAGS_LIST',
                    ['pgm_value' => (string) $value['id']],
                    [
                        'tags' => 'pgm_value2',
                    ],
                    [],
                );

                $getHashTagsData = $this->getDataGencode(
                    'FP_HASHTAGS_LIST',
                    ['pgm_value' => (string) $value['id']],
                    [
                        'hashtags' => 'pgm_value2',
                    ],
                    [],
                );

                $getTags = [];
                if (!empty($getTagsData)) {
                    foreach ($getTagsData as $tagItem) {
                        if (isset($tagItem['tags'])) {
                            $getTags[] = $tagItem['tags'];
                        }
                    }
                }

                $getHashTags = [];
                if (!empty($getHashTagsData)) {
                    foreach ($getHashTagsData as $hashTagItem) {
                        if (isset($hashTagItem['hashtags'])) {
                            $getHashTags[] = $hashTagItem['hashtags'];
                        }
                    }
                }

                return array_merge($value->toArray(), [
                    'url' => $getDataGencode['url'] ?? '',
                    'desc' => $getDataGencode['desc'] ?? '',
                    'is_main' => !empty($getDataGencode['is_main']) ? $getDataGencode['is_main'] : '0',
                    'is_published' => !empty($getPublished) && !empty($getPublished['is_published']) ? 1 : 0,
                    'categories_users' => $getCategoriesUsers ?? null,
                    'tags' => $getTags ?? [],
                    'hashtags' => $getHashTags ?? [],
                ]);
            })->filter();

            if ($isPaginated) {
                return [
                    'data' => array_values($hasil->toArray()),
                    'pagination' => [
                        'current_page' => $items->currentPage(),
                        'last_page' => $items->lastPage(),
                        'per_page' => $items->perPage(),
                        'total' => $items->total(),
                        'from' => $items->firstItem(),
                        'to' => $items->lastItem(),
                    ]
                ];
            }

            return array_values($hasil->toArray());
        } else {
            if (count($filter) > 0) {
                foreach ($filter as $value) {
                    $column = $value['cols'] ?? $value['field'] ?? null;
                    $operator = $value['param'] ?? $value['params'] ?? '=';
                    $filterValue = $value['value'] ?? null;

                    if (!$column || $filterValue === null) {
                        continue;
                    }

                    $filterValue = $normalizeFilterValue($filterValue);
                    $dataBuild->where($column, $operator, $filterValue);
                }
            }

            $data = (clone $dataBuild)->with([
                'formMaster' => function ($f) {
                    $f->where('cfm_parent_id', 0);
                    $f->with('formDetail.formAnswer');
                    $f->with('allChildrenContent.formDetail.formAnswer');
                    $f->orderBy('cfm_seq_name', 'asc');
                }
            ])->where('cfmt_quiz_flag', 0)
                ->orderBy('cms_form_mstr_title.created_at', 'desc')
                ->get();
        }

        $hasilHeader = $this->getHeaderAllForms($data->toArray());

        // return $hasilHeader;
        $hasil = [];
        foreach ($hasilHeader as $key => $value) {
            $hasil[] = [
                'label' => $value['title'] . ' (' . count($value['forms']) . ' Rows Content)',
                'value' => $value
            ];
        }

        return response([
            'status' => count($hasil) > 0,
            'data' => $hasil
        ]);
    }

    public function showDetail(Request $request)
    {
        return $this->show(
            $request->id,
            $request->tags ?? '',
            $request->limit ?? 5,
            $request->orderBy ?? [],
            $request->isPublisedOnly ?? false,
            $request->isPaginated ?? false,
            $request->page ?? 1,
            $request->users ?? '',
            $request->filter ?? []
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    public function updateStatus(Request $request)
    {
        $formTitle = FormMasterTitle::find($request->id);
        if (!$formTitle) {
            return response(['status' => false, 'message' => 'Form not found'], 404);
        }

        $formTitle->cfmt_status = $request->input('status', 'draft');
        $formTitle->save();

        return response(['status' => true, 'message' => 'Status updated successfully']);
    }

    public function saveSetupTraining(Request $request)
    {
        $idRef = $request->idRef;
        if (!$idRef || !$request->has('setupTraining')) {
            return response(['status' => false, 'message' => 'Missing idRef or setupTraining'], 400);
        }

        foreach ($request->setupTraining as $key => $valueSetup) {
            PortalGencode::updateOrCreate([
                'pgm_code' => 'FORMS_SETUP',
                'pgm_value' => $idRef,
                'pgm_desc' => $key,
            ], [
                'pgm_code' => 'FORMS_SETUP',
                'pgm_value' => $idRef,
                'pgm_value2' => is_array($valueSetup) ? json_encode($valueSetup) : $valueSetup,
                'pgm_desc' => $key,
                'pgm_created_by' => $request->header('username'),
            ]);
        }

        return response(['status' => true, 'message' => 'Setup training saved successfully']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $form = FormMasterTitle::withTrashed()->find($id);
        if (!$form) {
            return response(['status' => false, 'message' => 'Form not found'], 404);
        }

        $form->delete();

        return response([
            'status' => true,
            'message' => 'Form moved to trash successfully.'
        ]);
    }

    public function restore($id)
    {
        $form = FormMasterTitle::withTrashed()->find($id);
        if (!$form) {
            return response(['status' => false, 'message' => 'Form not found'], 404);
        }

        $form->restore();

        return response([
            'status' => true,
            'message' => 'Form restored successfully.'
        ]);
    }

    public function trashed()
    {
        $forms = FormMasterTitle::onlyTrashed()
            ->with(['formMaster', 'quizSetup', 'shared'])
            ->get();

        return response([
            'status' => true,
            'data' => $forms
        ]);
    }

    public function destroyAnswers($id, $batchID)
    {
        $delete = FormAnswerUserDet::where('cfm_id', $id)
            ->where('cfaud_batch', $batchID)
            ->delete();

        if ($delete) {
            return $this->handleResponse([], 'Form answers deleted successfully.');
        } else {
            return $this->handleError('Failed to delete form answers.', []);
        }
    }

    public function viewByLinkForm(Request $request, $link)
    {
        $getID = FormShareDet::where('cfsd_gen_link', $link)->first();
        if (!$getID) {
            $app = PortalApp::where('am_app_url', 'like', '%' . $link . '%')->first();
            if ($app && str_starts_with($app->am_app_code, 'FRM-')) {
                $formId = substr($app->am_app_code, 4);
                $getID = (object) ['cfmt_id' => $formId];
            } else {
                return response(['status' => false, 'message' => 'Form link not found'], 404);
            }
        }

        $data = formMasterTitle::with([
            'formMaster' => function ($f) {
                $f->where('cfm_parent_id', 0);
                $f->with('formDetail.formAnswer');
                $f->with('allChildrenContent.formDetail.formAnswer');
            }
        ])->with(['quizSetup', 'shared'])->where('cfmt_quiz_flag', 0)
            ->where('id', $getID->cfmt_id)
            ->get();

        $hasilHeader = $this->getHeaderAllForms($data->toArray());

        if (empty($hasilHeader)) {
            return response([
                'status' => false,
                'message' => 'Form not found'
            ], 404);
        }

        $formData = $hasilHeader[0];

        if (($formData['status'] ?? 'draft') === 'closed') {
            return response([
                'status' => false,
                'message' => 'This form is no longer accepting responses.'
            ], 403);
        }

        $setup = $formData['setupTraining'] ?? [];
        $memberBypass = false;
        $isMainMenuLink = isset($getID->cfsd_is_menu) && $getID->cfsd_is_menu == 1;
        if (!$isMainMenuLink) {
            $appFallback = PortalApp::where('am_app_url', 'like', '%' . $link . '%')->first();
            $isMainMenuLink = $appFallback && str_starts_with($appFallback->am_app_code, 'FRM-');
        }
        if (!$isMainMenuLink) {
            $listOn = in_array($setup['specificUserSetViewHistory'] ?? null, [true, 1, '1', 'true'], true);
            if ($listOn) {
                $list = is_array($setup['listSpecificUserRoleSetViewHistory'] ?? null) ? $setup['listSpecificUserRoleSetViewHistory'] : [];
                $username = $request->header('username') ?? '';
                $roleId = $request->header('roleid') ?? '';
                if (($setup['userView'] ?? 'user') === 'role') {
                    $memberBypass = collect($list)->contains(fn($r) => (string) $r === (string) $roleId);
                } else {
                    $memberBypass = collect($list)->contains(fn($u) => strtolower(trim((string) $u)) === strtolower(trim((string) $username)));
                }
            }
        } else {
            $memberBypass = true;
        }
        $addPeriod = isset($setup['addPeriod']) && ($setup['addPeriod'] === true || $setup['addPeriod'] == 1 || $setup['addPeriod'] === '1');

        if (!$memberBypass && $addPeriod) {
            $now = now()->format('Y-m-d H:i');
            $start = $setup['startQuiz'] ?? null;
            $end = $setup['endQuiz'] ?? null;

            if (!empty($start) && $now < $start) {
                return response([
                    'status' => false,
                    'message' => 'This form is not yet available. It will open on ' . $start . '.'
                ], 403);
            }

            if (!empty($end) && $now > $end) {
                return response([
                    'status' => false,
                    'message' => 'This form is no longer available. The deadline was ' . $end . '.'
                ], 403);
            }
        }

        $connectedMRS = $this->getConnectedMRS((string) $getID->cfmt_id);
        if (!$memberBypass && !empty($connectedMRS) && !empty($connectedMRS->id)) {
            $periodUsername = $request->header('username');
            if (!empty($periodUsername)) {
                $periodRow = PortalGencode::where('pgm_code', 'MRS_FORM_PERIOD')
                    ->whereRaw("CAST(pgm_value AS varchar(max)) = ?", [(string) $connectedMRS->id])
                    ->whereRaw("CAST(pgm_value2 AS varchar(max)) = ?", [(string) $periodUsername])
                    ->first();
                if (!empty($periodRow) && !empty($periodRow->pgm_value3)) {
                    $period = json_decode($periodRow->pgm_value3, true);
                    if (!empty($period['from']) && !empty($period['to'])) {
                        $periodNow = now()->format('Y-m-d H:i');
                        $toBound = strlen($period['to']) <= 10 ? $period['to'] . ' 23:59:59' : $period['to'];
                        if ($periodNow < $period['from'] || $periodNow > $toBound) {
                            return response([
                                'status' => false,
                                'message' => 'This form is outside the active period.'
                            ], 403);
                        }
                    }
                }
            }
        }

        $hasil = [
            'label' => $formData['title'] . ' (' . count($formData['forms']) . ' Rows Content)',
            'value' => $formData
        ];

        return response([
            'status' => count($hasil) > 0,
            'data' => $hasil
        ]);
    }

    public function cloneForm(Request $request)
    {
        $sourceId = $request->input('id');
        $newYear = $request->input('year', null);
        $newTitle = $request->input('title', null);

        $source = FormMasterTitle::with([
            'formMaster' => function ($f) {
                $f->where('cfm_parent_id', 0);
                $f->with('formDetail');
                $f->with('allChildrenContent');
            }
        ])->find($sourceId);

        if (!$source) {
            return $this->handleError('Source form not found.', []);
        }

        $newTitle = $newTitle ?? $source->cfmt_title . ' (Copy)';

        $newMaster = FormMasterTitle::create([
            'p_u_username' => $request->header('username'),
            'cfmt_title' => $newTitle,
            'cfmt_quiz_flag' => $source->cfmt_quiz_flag,
            'cfmt_status' => 'draft',
            'cfmt_year' => $newYear,
        ]);

        $idMap = [];

        foreach ($source->formMaster as $block) {
            $newBlock = FormMaster::create([
                'p_u_username' => $request->header('username'),
                'cfmt_id' => $newMaster->id,
                'cfm_type' => $block->cfm_type,
                'cfm_seq_name' => $block->cfm_seq_name,
                'cfm_content' => $block->cfm_content,
                'cfm_parent_id' => 0,
                'cfm_required' => $block->cfm_required,
            ]);

            $idMap[$block->id] = $newBlock->id;

            foreach ($block->formDetail as $detail) {
                FormMultiDet::create([
                    'cfm_id' => $newBlock->id,
                    'cfmd_value' => $detail->cfmd_value,
                    'cfmd_name' => $detail->cfmd_name,
                ]);
            }

            foreach ($block->allChildrenContent as $child) {
                $newChild = FormMaster::create([
                    'p_u_username' => $request->header('username'),
                    'cfmt_id' => $newMaster->id,
                    'cfm_type' => $child->cfm_type,
                    'cfm_seq_name' => $child->cfm_seq_name,
                    'cfm_content' => $child->cfm_content,
                    'cfm_parent_id' => $newBlock->id,
                    'cfm_required' => $child->cfm_required,
                ]);

                $idMap[$child->id] = $newChild->id;

                foreach ($child->formDetail as $childDetail) {
                    FormMultiDet::create([
                        'cfm_id' => $newChild->id,
                        'cfmd_value' => $childDetail->cfmd_value,
                        'cfmd_name' => $childDetail->cfmd_name,
                    ]);
                }
            }
        }

        if ($source->cfmt_quiz_flag === 1 && $source->quizSetup) {
            FormSetupDet::create([
                'cfmt_id' => $newMaster->id,
                'cfsd_res_show' => $source->quizSetup->cfsd_res_show,
                'cfsd_ans_show' => $source->quizSetup->cfsd_ans_show,
                'cfsd_rand_quest' => $source->quizSetup->cfsd_rand_quest,
                'cfsd_ans_loc' => $source->quizSetup->cfsd_ans_loc,
                'cfsd_timer' => $source->quizSetup->cfsd_timer,
                'cfsd_timer_quest' => $source->quizSetup->cfsd_timer_quest,
                'cfsd_hours' => $source->quizSetup->cfsd_hours,
                'cfsd_min' => $source->quizSetup->cfsd_min,
                'cfsd_sec' => $source->quizSetup->cfsd_sec,
                'cfsd_min_pass' => $source->quizSetup->cfsd_min_pass,
                'cfsd_start_quiz' => '',
                'cfsd_end_quiz' => '',
                'cfsd_real_start_quiz' => '',
                'cfsd_real_end_quiz' => '',
                'cfsd_quest_limit' => $source->quizSetup->cfsd_quest_limit,
            ]);
        } else {
            $sourceSetup = $this->getSetupFormsForForm($sourceId);
            if (!empty($sourceSetup)) {
                foreach ($sourceSetup as $key => $value) {
                    if (in_array($key, ['isRPA', 'rpaId', 'rpaParams', 'isApproval', 'isAPI', 'apiOpt', 'isNotif', 'connectedMRS'])) {
                        continue;
                    }
                    PortalGencode::updateOrCreate([
                        'pgm_code' => 'FORMS_SETUP',
                        'pgm_value' => $newMaster->id,
                        'pgm_desc' => $key,
                    ], [
                        'pgm_code' => 'FORMS_SETUP',
                        'pgm_value' => $newMaster->id,
                        'pgm_value2' => is_array($value) ? json_encode($value) : $value,
                        'pgm_desc' => $key,
                        'pgm_created_by' => $request->header('username'),
                    ]);
                }
            }
        }

        return $this->handleResponse([
            'id' => $newMaster->id,
            'title' => $newMaster->cfmt_title,
        ], 'Form cloned successfully!');
    }

    public function getCompletionStatus($id)
    {
        $form = FormMasterTitle::with(['shared'])->find($id);

        if (!$form) {
            return $this->handleError('Form not found.', []);
        }

        $assignedUsers = FormShareDet::where('cfmt_id', $id)
            ->whereNotNull('cfsd_to')
            ->where('cfsd_to', '!=', '')
            ->pluck('cfsd_to')
            ->unique()
            ->values()
            ->toArray();

        $roleUsers = FormShareDet::where('cfmt_id', $id)
            ->whereNotNull('cfsd_role_id')
            ->where('cfsd_role_id', '!=', '')
            ->get();

        foreach ($roleUsers as $ru) {
            $usersInRole = DB::connection('sqlsrv')->table('portal_users_det')
                ->join('portal_user_role_det', 'u_username', 'purd_username')
                ->where('purd_rlid', $ru->cfsd_role_id)
                ->where('pud_is_active', 1)
                ->pluck('u_username')
                ->toArray();
            $assignedUsers = array_merge($assignedUsers, $usersInRole);
        }

        $assignedUsers = array_unique($assignedUsers);

        $submittedUsers = FormAnswerUserDet::where('cfm_id', $id)
            ->select('p_u_username', DB::raw('MAX(cfaud_batch) as latest_batch'), DB::raw('MAX(created_at) as submitted_at'))
            ->groupBy('p_u_username')
            ->pluck('p_u_username')
            ->toArray();

        $result = [];
        foreach ($assignedUsers as $user) {
            $result[] = [
                'username' => $user,
                'submitted' => in_array($user, $submittedUsers),
            ];
        }

        return $this->handleResponse([
            'total_assigned' => count($assignedUsers),
            'total_submitted' => count($submittedUsers),
            'users' => $result,
        ], 'Completion status retrieved.');
    }

    public function viewByID($id, $username = '')
    {
        $data = formMasterTitle::with([
            'formMaster' => function ($f) {
                $f->where('cfm_parent_id', 0);
                $f->with('formDetail.formAnswer');
                $f->with('allChildrenContent.formDetail.formAnswer');
            }
        ])->with(['quizSetup', 'shared'])
            ->whereHas('formMaster')
            ->where('id', $id)
            ->first();

        if (empty($data)) {
            return response([
                'status' => false,
                'message' => 'Form not found'
            ]);
        }

        if ($data && ($data->cfmt_quiz_flag == 2 || $data->cfmt_quiz_flag == 3)) {
            $getDataGencode = $this->getDataGencode(
                'URL_PAGE_GEN',
                ['pgm_value' => $id],
                [
                    'url' => 'pgm_desc|string',
                    'desc' => 'pgm_desc2|string',
                    'is_main' => 'pgm_value2|string',
                ],
                [],
                true,
                false
            );

            if ($data->cfmt_quiz_flag == 3) {
                $getTagsData = $this->getDataGencode(
                    'FP_TAGS_LIST',
                    ['pgm_value' => $id],
                    [
                        'tags' => 'pgm_value2|string',
                        'tags_desc' => 'pgm_desc|string',
                    ],
                    [],
                    false,
                    false
                );

                $getTags = [];
                if (!empty($getTagsData)) {
                    foreach ($getTagsData as $tagItem) {
                        if (isset($tagItem['tags'])) {
                            $getTags[] = $tagItem['tags'];
                        }
                    }
                }

                $getHashTagsData = $this->getDataGencode(
                    'FP_HASHTAGS_LIST',
                    ['pgm_value' => $id],
                    [
                        'hashtags' => 'pgm_value2|string',
                        'hashtags_desc' => 'pgm_desc|string',
                    ],
                    [],
                    false,
                    false
                );

                $getHashTags = [];
                if (!empty($getHashTagsData)) {
                    foreach ($getHashTagsData as $hashTagItem) {
                        if (isset($hashTagItem['hashtags'])) {
                            $getHashTags[] = $hashTagItem['hashtags'];
                        }
                    }
                }

                $getPublished = $this->getDataGencode(
                    'FP_PUBLISH_POSTS',
                    ['pgm_value' => $id],
                    [
                        'is_published' => 'pgm_value2|date',
                    ],
                    $request->orderBy ?? [],
                    true,
                    false
                );

                $hasil = null;
                // return response($getPublished);

                $getSubscription = $this->getDataGencode(
                    'FP_SUBSCRIBE_POSTS',
                    ['pgm_value3' => $username],
                    [
                        'type' => 'pgm_value|string',
                        'value' => 'pgm_value2|string',
                        'user_id' => 'pgm_value3|string',
                    ],
                );

                $hasil = array_merge($data->toArray(), [
                    'url' => $getDataGencode['url'] ?? '',
                    'desc' => $getDataGencode['desc'] ?? '',
                    'is_main' => !empty($getDataGencode['is_main']) ? $getDataGencode['is_main'] : '0',
                    'is_published' => !empty($getPublished) && !empty($getPublished['is_published']) ? 1 : 0,
                    'tags' => $getTags ?? [],
                    'hashtags' => $getHashTags ?? [],
                    'subscription' => $getSubscription
                ]);
            } else {
                $hasil = array_merge($data->toArray(), [
                    'url' => $getDataGencode['url'] ?? '',
                    'desc' => $getDataGencode['desc'] ?? '',
                    'is_main' => !empty($getDataGencode['is_main']) ? $getDataGencode['is_main'] : '0',
                    'tags' => [],
                    'hashtags' => [],
                    'subscription' => !empty($getSubscription) ? $getSubscription : []
                ]);
            }

            if (empty($hasil)) {
                return response([
                    'status' => false,
                    'message' => 'Form not found'
                ]);
            } else {
                $hasilHeader = $this->getHeaderAllForms([$hasil]);

                // return response($hasilHeader);

                $hasils = [
                    'label' => $hasilHeader[0]['title'] . ' (' . count($hasilHeader[0]['forms']) . ' Rows Content)',
                    'value' => $hasilHeader[0]
                ];

                return response([
                    'status' => count($hasils) > 0,
                    'data' => $hasils
                ]);
            }
        }


        $hasilHeader = $this->getHeaderAllForms([$data->toArray()]);

        $hasil = [
            'label' => $hasilHeader[0]['title'] . ' (' . count($hasilHeader[0]['forms']) . ' Rows Content)',
            'value' => $hasilHeader[0]
        ];

        return response([
            'status' => count($hasil) > 0,
            'data' => $hasil
        ]);
    }

    public function viewBySlug(Request $request, $slug)
    {
        // Check if slug is an integer
        if (is_numeric($slug) && ctype_digit($slug)) {
            $checkByID = $this->viewByID($slug, $request->header('username'))->getOriginalContent();

            if ($checkByID['status'] == true) {
                return $checkByID;
            }
        }

        $getGencode = PortalGencode::where('pgm_code', 'URL_PAGE_GEN')
            ->where('pgm_desc', $slug)
            ->first();

        return $getGencode->pgm_value ? $this->viewByID((int)$getGencode->pgm_value, $request->header('username'))->getOriginalContent() : response([
            'status' => false,
            'message' => 'Form not found'
        ]);

        if (!$getGencode || !$getGencode->pgm_value) {
            return response([
                'status' => false,
                'message' => 'Form not found'
            ]);
        }

        return $this->viewByID($getGencode->pgm_value, $request->header('username'))->getOriginalContent();
    }

    public function updateAMSMapping(Request $request)
    {
        return $this->handleResponse([], 'AMS Mapping updated successfully.');
    }

    public function sendApproval(Request $request)
    {
        // Validate the request
        $request->validate([
            'idRef' => 'required|integer',
            'username' => 'required|string',
        ]);

        $checkSetup = $this->getSetupFormsForForm($request->idRef);

        $dataAnswers = $this->showHistory(new Request(), $request->idRef, $request->batch_id)->getOriginalContent()['data']['data'][0] ?? [];

        $approvalCode = $checkSetup['approvalCode'] ?? null;
        if (!empty($checkSetup['approvalConditions']) && is_array($checkSetup['approvalConditions'])) {
            $fieldId = $checkSetup['approvalConditionField'] ?? null;
            if (!$fieldId && !empty($checkSetup['defaultFilterData']) && is_array($checkSetup['defaultFilterData'])) {
                $firstFilter = $checkSetup['defaultFilterData'][0];
                $colVal = $firstFilter['cols']['value'] ?? $firstFilter['cols']['field'] ?? $firstFilter['cols']['cols'] ?? null;
                if ($colVal) $fieldId = str_replace('CMS_REPORT_', '', $colVal);
            }
            if ($fieldId) {
                $fieldKey = 'CMS_REPORT_' . $fieldId;
                $fieldVal = $dataAnswers[$fieldKey] ?? null;
                foreach ($checkSetup['approvalConditions'] as $cond) {
                    if (isset($cond['value']) && (string) $cond['value'] === (string) $fieldVal && !empty($cond['approvalCode'])) {
                        $approvalCode = $cond['approvalCode'];
                        break;
                    }
                }
            }
        }

        $getMasterResponse = $this->viewApprovalMasterByApprvCode($approvalCode);
        $getMasterContent = json_decode($getMasterResponse->getContent(), true);
        $getMasterData = isset($getMasterContent['data']) ? $getMasterContent['data'] : null;

        // You need to provide actual values for HSCD_DOCNO and item_det if required by your business logic.
        // For now, we will use placeholders or empty values to avoid undefined variable errors.
        $getUrl = config('app.url');
        $getApproval = $this->approveAction(new ApprovalRunningApproveActionRequest([
            'username' => $request->username,
            'amsm_id' => $getMasterData['id'] ?? null,
            'stat' => 1,
            'remarks' => 'Sending approval CMS!!',
            'data' => $dataAnswers,
            'onApproval' => [
                'methods' => 'post',
                'params' => [
                    'amstd_token' => 'token',
                    'amshd_remarks' => 'Remarks',
                ],
                    'url' => $getUrl . '/api/cms/updateApprovalStatus'
                // 'url' => 'http://localhost/STX/stx-api/public/api/cms/updateApprovalStatus'
            ],
            'onDone' => [
                'methods' => 'post',
                'params' => [
                    'amstd_token' => 'token',
                    'amshd_remarks' => 'Remarks',
                ],
                'url' => $getUrl . '/api/cms/updateApprovalStatus'
                // 'url' => 'http://localhost/STX/stx-api/public/api/cms/updateApprovalStatus'
            ],
            'msgkey' => ''
        ]))->getOriginalContent();

        // return $getApproval;
        if ($getApproval['status'] == true) {
            // amstd_token
            FormAMSMapDet::updateOrCreate(
                ['cfamd_cfm_id' => $request->idRef],
                [
                    'cfamd_cfm_id' => $request->idRef,
                    'cfamd_cfaud_batch' => $request->batch_id,
                    'cfamd_amstd_token' => $getApproval['data']['amstd_token'],
                    'cfamd_desc' => $getApproval['data']['amshd_remarks'],
                ]
            );
        }

        return $getApproval;
    }

    public function sendAPIFormsSubmitted(Request $request)
    {
        // Validate the request
        $request->validate([
            'idRef' => 'required|integer',
            'username' => 'required|string',
            'apiUrl' => 'required|string',
            'method' => 'required|string',
            'headers' => 'nullable|string',
            'isDownload' => 'required|boolean',
            'params' => 'nullable|array',
        ]);
        // Answers are stored AFTER the API call, so on a first submission the
        // history query is empty. Fall back to an empty base and rely on the
        // request params merged below.
        $historyContent = $this->showHistory(new Request(), $request->idRef, $request->batch_id)->getOriginalContent();
        $dataAnswers = $historyContent['data']['data'][0] ?? [];
        $headersArray = [];
        if (!empty($request->headers)) {
            $headersArray = json_decode($request->headers, true);
        }
        // Merge params from request if provided
        if (!empty($request->params)) {
            $dataAnswers = array_merge($dataAnswers, $request->params);
        }
        $client = new \GuzzleHttp\Client();

        try {
            $options = [
                'headers' => $headersArray,
                'json' => $dataAnswers,
                'timeout' => 60,
                'connect_timeout' => 30,
                'allow_redirects' => true,
            ];

            if ($request->isDownload) {
                $contentType = '';
                // Make a HEAD request first to get content type
                logger('Starting HEAD request to determine content type for download...');
                try {
                    $headOptions = array_merge($options, ['timeout' => 10, 'connect_timeout' => 5]);
                    $headResponse = $client->head($request->input('apiUrl'), $headOptions);
                    $contentType = $headResponse->getHeaderLine('Content-Type');
                } catch (\Exception $e) {
                    logger('HEAD request failed: ' . $e->getMessage());
                    // If HEAD fails, we'll determine extension from actual response later
                    $response = [
                        'status' => false,
                        'message' => 'API request failed',
                        'error' => $e->getMessage(),
                        'request' => $request->all(),
                        'params_sent' => $dataAnswers
                    ];

                    return $response;
                }

                logger('Header is done, content type: ' . $contentType);
                // Determine extension from content type
                $extension = 'pdf'; // default
                if (strpos($contentType, 'application/pdf') !== false) {
                    $extension = 'pdf';
                } elseif (strpos($contentType, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') !== false) {
                    $extension = 'xlsx';
                } elseif (strpos($contentType, 'application/vnd.ms-excel') !== false) {
                    $extension = 'xls';
                } elseif (strpos($contentType, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') !== false) {
                    $extension = 'docx';
                } elseif (strpos($contentType, 'application/msword') !== false) {
                    $extension = 'doc';
                } elseif (strpos($contentType, 'image/jpeg') !== false) {
                    $extension = 'jpg';
                } elseif (strpos($contentType, 'image/png') !== false) {
                    $extension = 'png';
                } elseif (strpos($contentType, 'text/csv') !== false) {
                    $extension = 'csv';
                } elseif (strpos($contentType, 'application/json') !== false) {
                    $extension = 'json';
                } elseif (strpos($contentType, 'text/plain') !== false) {
                    $extension = 'txt';
                }

                $downloadPath = Storage::disk('public')->path('downloads');
                if (!file_exists($downloadPath)) {
                    mkdir($downloadPath, 0755, true);
                }
                $fileName = time() . '_response.' . $extension;
                $options['sink'] = $downloadPath . '/' . $fileName;

                // After successful download, construct URL using APP_URL_DOWNLOAD env variable
                $downloadUrl = rtrim(env('APP_URL_DOWNLOAD', config('app.url') . '/storage/'), '/') . '/downloads/' . $fileName;
            }

            $httpMethod = strtoupper($request->input('method'));
            $apiUrl = $request->input('apiUrl');

            // For non-download requests, use form_params instead of json for better compatibility
            if (!$request->isDownload && $httpMethod !== 'GET') {
                unset($options['json']);
                $options['form_params'] = $dataAnswers;
            }

            $response = $client->request(
                $httpMethod,
                $apiUrl,
                $options,
            );

            logger('API request to ' . $apiUrl . ' completed with status ' . $response->getStatusCode());

            if ($request->isDownload) {
                return response()->json([
                    'status' => true,
                    'message' => 'File downloaded successfully',
                    'file_path' => $downloadUrl
                ]);
            }

            $response = [
                'status' => true,
                'message' => 'API request sent successfully',
                'response' => json_decode($response->getBody()->getContents(), true),
                'status_code' => $response->getStatusCode()
            ];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = [
                'status' => false,
                'message' => 'API request failed',
                'error' => $e->getMessage()
            ];
        }

        return array_merge([
            'request' => $request->all()
        ], $response);
    }
}