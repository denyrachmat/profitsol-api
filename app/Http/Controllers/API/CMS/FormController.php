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
        ]);

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
                        if ($checkSetup['isRPA'] == 1) {
                            $descUrl = 'rpa';
                        } else {
                            $descUrl = 'cms';
                        }

                        if ($checkSetup['isApproval'] == 1) {
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

                        $getDataForDet = $this->showHistory(new Request(), $request->idRef);
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

        // Setup Share forms
        if (isset($request->shareForms) && !empty($request->idRef)) {
            $randomString = Str::random(30);
            foreach ($request->shareForms as $keyShare => $valueShare) {
                $cekIDRoles = PortalRoleUserMap::where('u_username', $valueShare)->first();

                FormShareDet::updateOrCreate([
                    'cfmt_id' => $request->idRef,
                    'cfsd_to' => $valueShare,
                ], [
                    'cfmt_id' => $request->idRef,
                    'p_u_username' => $request->header('username'),
                    'cfsd_to' => $valueShare,
                    'cfsd_gen_link' => $randomString,
                    'cfsd_role_id' => isset($request->shareFormsIsRoles) && $request->shareFormsIsRoles ? $cekIDRoles->rm_role_id : '',
                    'cfsd_is_menu' => isset($request->shareFormsIsMainMenu) && $request->shareFormsIsMainMenu ? $request->shareFormsIsMainMenu : 0
                ]);

                // If Form Added to information
                if (!isset($request->shareFormsIsMainMenu) || $request->shareFormsIsMainMenu == 0) {
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
                } else { // If Form Added to Main Menu
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

                    // Insert parent menu
                    // PortalRoleAppMap::updateOrCreate([
                    //     'rm_role_id' => $cekIDRoles->rm_role_id,
                    //     'am_app_id' => $request->selectedSharedMenu,
                    // ], [
                    //     'u_username' => $request->header('username'),
                    //     'rm_role_id' => $cekIDRoles->rm_role_id,
                    //     'am_app_id' => $request->selectedSharedMenu,
                    //     'am_app_parent' => null
                    // ]);

                    // // Insert the forms
                    // PortalRoleAppMap::updateOrCreate([
                    //     'rm_role_id' => $cekIDRoles->rm_role_id,
                    //     'am_app_id' => $appInsert->am_app_code,
                    // ], [
                    //     'u_username' => $request->header('username'),
                    //     'rm_role_id' => $cekIDRoles->rm_role_id,
                    //     'am_app_id' => $appInsert->am_app_code,
                    //     'am_app_parent' => $request->selectedSharedMenu
                    // ]);
                }
            }
        }

        if (!empty($request->idRef)) {
            function extractIds($array, &$ids = [])
            {
                if (isset($array['id']) && is_int($array['id'])) {
                    $ids[] = $array['id'];
                }

                foreach ($array as $value) {
                    if (is_array($value)) {
                        extractIds($value, $ids);
                    }
                }

                return $ids;
            }

            $getListUpdatedID = extractIds($data);

            if (count($getListUpdatedID) > 0) {
                FormMaster::where('cfmt_id', $insertMaster->id)
                    ->whereNotIn('id', $getListUpdatedID)
                    ->delete();
                FormAnswerDet::where('cfm_id', $insertMaster->id)
                    ->whereNotIn('cfmd_id', $getListUpdatedID)
                    ->delete();

                FormLogicsDet::where('cfm_id', $insertMaster->id)
                    ->whereNotIn('cfm_id', $getListUpdatedID)
                    ->delete();
                // FormAnswerUserDet::where('cfm_id', $insertMaster->id)
                //     ->whereNotIn('cfmd_id', $getListUpdatedID)
                //     ->delete();
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
            'id' => $insertMaster->id
        ], 'Data Found !');
        // return response($hasil);
    }

    public function storeAnswers(Request $request)
    {
        $getID = FormAnswerUserDet::where('cfm_id', $request->id)
            ->where('p_u_username', $request->header('username'))
            ->orderBy('created_at', 'desc')
            ->first();

        if (empty($getID)) {
            $getDeletedID = FormAnswerUserDet::withTrashed()->where('cfm_id', $request->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $nextID = empty($getDeletedID) ? 1 : $getDeletedID['cfaud_batch'] + 1;
        } else {
            $nextID = $getID['cfaud_batch'] + 1;
        }

        $cekForm = FormMaster::where('cfmt_id', $request->id)->where('cfm_type', 'form')->where('cfm_required', 1)->get()->toArray();

        $spreadAnswer = [];
        foreach ($request->ans as $keyAns => $valueAns) {
            foreach ($valueAns as $keyAnsDet => $valueAnsDet) {
                $spreadAnswer[$keyAnsDet] = $valueAnsDet;
            }
        }

        $hasilError = [];
        foreach ($cekForm as $keyCekForms => $valueCekForms) {
            if (!isset($spreadAnswer[$valueCekForms['id']])) {
                $getContent = json_decode($valueCekForms['cfm_content']);
                $hasilError[$valueCekForms['id']] = [$getContent->label . ' is required'];
            }
        }

        if (count($hasilError) > 0) {
            return $this->handleError('There is required field not filled yet !', $hasilError);
        }

        $hasil = [];
        foreach ($spreadAnswer as $key => $value) {
            $hasil[] = FormAnswerUserDet::create([
                'p_u_username' => $request->header('username'),
                'cfaud_batch' => $nextID,
                'cfm_id' => $request->id,
                'cfmd_id' => $key,
                'cfm_val' => (string) $value,
            ]);
        }

        $checkSetup = $this->getSetupFormsForForm($request->id);
        // return $checkSetup;
        if ($checkSetup['isRPA'] == 1) {
            $getRPAId = $checkSetup['rpaId'];
            $params = $this->buildNestedParams($checkSetup['rpaParams'], $request->id, $nextID);

            app('App\Http\Controllers\API\RPA\RPAHistController')->store(new Request([
                'prh_prmid' => $getRPAId['id'],
                'prh_robotnm' => $getRPAId['prm_name'],
                'prh_command' => json_encode($params),
                'prh_flag' => 0, // pending
                'prh_result' => 'Starting RPA',
                'prh_cfaud_id' => $request->id,
                'prh_cfaud_batch_id' => $nextID,
            ]));
        }

        if ($checkSetup['isApproval'] == 1) {
            $this->sendApproval(new Request([
                'idRef' => $request->id,
                'username' => $request->header('username'),
            ]));

        }

        return $this->handleResponse($hasil, 'Form submited !');

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, $tags = '', $showMax = 0, $orderBy = [], $isPublisedOnly = false, $isPaginated = false, $page = 1)
    {
        // return [$id, $tags, $showMax, $orderBy, $isPublisedOnly, $isPaginated, $page];
        $dataBuild = formMasterTitle::select('cms_form_mstr_title.*', 'pgTags.pgm_value2 as tags')->with(['quizSetup', 'shared']);

        if ($id === 'quiz') {
            $data = (clone $dataBuild)->with([
                'formMaster' => function ($f) {
                    $f->where('cfm_parent_id', 0);
                    $f->with('formDetail.formAnswer');
                    $f->with('allChildrenContent.formDetail.formAnswer');
                    $f->orderBy('cfm_seq_name', 'asc');
                }
            ])->where('cfmt_quiz_flag', 1)
                ->get();
        } elseif ($id === 'page' || $id === 'post') {
            $dataBuild->where('cfmt_quiz_flag', $id === 'page' ? 2 : 3);

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
                $decodedTags = base64_decode($tags);
                $dataBuild->whereIn('pgTags.pgm_value2', json_decode($decodedTags, true));
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

                $getTagsData = $this->getDataGencode(
                    'FP_TAGS_LIST',
                    ['pgm_value' => (string)$value['id']],
                    [
                        'tags' => 'pgm_value2',
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

                return array_merge($value->toArray(), [
                    'url' => $getDataGencode['url'] ?? '',
                    'desc' => $getDataGencode['desc'] ?? '',
                    'is_main' => !empty($getDataGencode['is_main']) ? $getDataGencode['is_main'] : '0',
                    'is_published' => $getPublished ? 1 : 0,
                    'tags' => $getTags ?? [],
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
            $data = (clone $dataBuild)->with([
                'formMaster' => function ($f) {
                    $f->where('cfm_parent_id', 0);
                    $f->with('formDetail.formAnswer');
                    $f->with('allChildrenContent.formDetail.formAnswer');
                    $f->orderBy('cfm_seq_name', 'asc');
                }
            ])->where('cfmt_quiz_flag', 0)->get();
        }

        // return $data;

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
            $request->page ?? 1
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Delete all related data
        formMasterTitle::where('id', $id)->delete();
        $fmIds = FormMaster::where('cfmt_id', $id)->pluck('id')->toArray();
        FormMaster::where('cfmt_id', $id)->delete();

        foreach ($fmIds as $idx) {
            FormMultiDet::where('cfm_id', $idx)->delete();
            FormAnswerDet::where('cfmd_id', $idx)->delete();
            FormLogicsDet::where('cfm_id', $idx)->delete();
            FormAnswerUserDet::where('cfm_id', $idx)->delete();
        }

        FormSetupDet::where('cfmt_id', $id)->delete();
        FormShareDet::where('cfmt_id', $id)->delete();

        // Delete related PortalGencode entries
        PortalGencode::where('pgm_code', 'URL_PAGE_GEN')
            ->where('pgm_value', $id)
            ->orWhere('pgm_code', 'FORMS_SETUP')
            ->where('pgm_value', $id)
            ->delete();

        // Delete related PortalApp entries
        PortalApp::where('am_app_code', 'FRM-' . $id)->delete();
        PortalRoleAppMap::where('am_app_id', 'FRM-' . $id)->delete();
        PortalRoleAppMap::where('am_app_parent', 'FRM-' . $id)->delete();

        return response([
            'status' => true,
            'message' => 'Form and related data deleted successfully.'
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

    public function viewByLinkForm($link)
    {
        $getID = FormShareDet::where('cfsd_gen_link', $link)->first();

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

        // return $hasilHeader;
        $hasil = [
            'label' => $hasilHeader[0]['title'] . ' (' . count($hasilHeader[0]['forms']) . ' Rows Content)',
            'value' => $hasilHeader[0]
        ];

        return response([
            'status' => count($hasil) > 0,
            'data' => $hasil
        ]);
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
            ->where('id', $id)
            ->first();

        // return response($data);

        if ($data->cfmt_quiz_flag == 2 || $data->cfmt_quiz_flag == 3) {
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

                // return response($getTags);

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
                $includeResult = true;
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
                    'is_published' => $getPublished && $getPublished['is_published'] ? 1 : 0,
                    'tags' => $getTags,
                    'subscription' => $getSubscription
                ]);
            } else {
                $hasil = array_merge($data->toArray(), [
                    'url' => $getDataGencode['url'] ?? '',
                    'desc' => $getDataGencode['desc'] ?? '',
                    'is_main' => !empty($getDataGencode['is_main']) ? $getDataGencode['is_main'] : '0',
                    'tags' => [],
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

        if (!$getGencode) {
            return response([
                'status' => false,
                'message' => 'Form not found'
            ]);
        }

        return $this->viewByID($getGencode->pgm_value, $request->header('username'))->getOriginalContent();
    }

    public function updateAMSMapping(Request $request, $id)
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

        $getMasterResponse = $this->viewApprovalMasterByApprvCode($checkSetup['approvalCode']);
        $getMasterContent = json_decode($getMasterResponse->getContent(), true);
        $getMasterData = isset($getMasterContent['data']) ? $getMasterContent['data'] : null;

        $dataAnswers = $this->showHistory(new Request(), $request->idRef, $request->batch_id)->getOriginalContent()['data']['data'][0];
        // FormAMSMapDet::update(
        //     ['amsm_id' => $getMasterData['id'] ?? null],
        //     ['cfmt_id' => $request->idRef]
        // );

        // You need to provide actual values for HSCD_DOCNO and item_det if required by your business logic.
        // For now, we will use placeholders or empty values to avoid undefined variable errors.
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
                'url' => 'http://192.168.100.32/public/api/cms/updateApprovalStatus'
                // 'url' => 'http://localhost/STX/stx-api/public/api/cms/updateApprovalStatus'
            ],
            'onDone' => [
                'methods' => 'post',
                'params' => [
                    'amstd_token' => 'token',
                    'amshd_remarks' => 'Remarks',
                ],
                'url' => 'http://192.168.100.32/public/api/cms/updateApprovalStatus'
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
}
