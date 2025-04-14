<?php

namespace App\Http\Controllers\API\CMS;

use App\Http\Controllers\API\PORTAL\BaseController as BaseController;
use App\Models\CMS\FormAnswerUserDet;
use App\Models\PORTAL\PortalApp;
use App\Models\PORTAL\PortalRoleAppMap;
use App\Models\PORTAL\PortalRoleUserMap;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Models\CMS\FormMaster;
use App\Models\CMS\FormMultiDet;
use App\Models\CMS\FormAnswerDet;
use App\Models\CMS\FormMasterTitle;
use App\Models\CMS\FormSetupDet;
use App\Models\CMS\FormShareDet;
use App\Models\PORTAL\PortalNotif;

use App\Traits\CMS\FormsTraits;

class FormController extends BaseController
{
    use FormsTraits;

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
            'cfmt_quiz_flag' => $request->isQuiz == true ? 1 : 0,
        ]);

        if (isset($request->idRef) && !empty($request->idRef)) {
            // FormMaster::where('cfmt_id', $request->idRef)->delete();
        }

        if (isset($request->setupTraining) && !empty($request->idRef)) {
            // FormSetupDet::where('cfmt_id', $request->idRef)->delete();
            $cekSetup = FormSetupDet::where('cfmt_id', $request->idRef)->first();

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
        }

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
                        'am_app_url' => 'forms/' . $randomString,
                        'am_app_icon' => $request->shareFormsMenuIcon,
                        'am_app_parent' => $request->selectedSharedMenu,
                        'am_local_form' => 1
                    ]);

                    // Insert parent menu
                    PortalRoleAppMap::updateOrCreate([
                        'rm_role_id' => $cekIDRoles->rm_role_id,
                        'am_app_id' => $request->selectedSharedMenu,
                    ], [
                        'u_username' => $request->header('username'),
                        'rm_role_id' => $cekIDRoles->rm_role_id,
                        'am_app_id' => $request->selectedSharedMenu,
                        'am_app_parent' => null
                    ]);

                    // Insert the forms
                    PortalRoleAppMap::updateOrCreate([
                        'rm_role_id' => $cekIDRoles->rm_role_id,
                        'am_app_id' => $appInsert->am_app_code,
                    ], [
                        'u_username' => $request->header('username'),
                        'rm_role_id' => $cekIDRoles->rm_role_id,
                        'am_app_id' => $appInsert->am_app_code,
                        'am_app_parent' => $request->selectedSharedMenu
                    ]);
                }
            }

            $getListUpdatedID = array_map(function ($item) {
                return $item['id'];
            }, array_filter($data, function ($item) {
                return isset($item['id']);
            }));

            if (count($getListUpdatedID) > 0) {
                FormMaster::where('cfmt_id', $insertMaster->id)
                    ->whereNotIn('id', $getListUpdatedID)
                    ->delete();
                FormAnswerDet::where('cfm_id', $insertMaster->id)
                    ->whereNotIn('cfmd_id', $getListUpdatedID)
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
            $hasil[] = $this->storingForms(
                $value,
                $request->header('username'),
                isset($request->ans) ? $request->ans : [],
                isset($request->exp) ? $request->exp : [],
                $insertMaster->id,
                0,
                $key,
            );
        }

        // FormMaster::where('cfmt_id', $request->idRef)
        //     ->whereNotIn('cfm_seq_name', $listPage)
        //     ->delete();

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
            $nextID = $getID['cfaud_batch'];
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
                'cfm_val' => $value,
            ]);
        }

        return $this->handleResponse($hasil, 'Form submited !');

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $dataBuild = formMasterTitle::with([
            'formMaster' => function ($f) {
                $f->where('cfm_parent_id', 0);
                $f->with('formDetail.formAnswer');
                $f->with('allChildrenContent.formDetail.formAnswer');
            }
        ])->with(['quizSetup', 'shared']);

        if ($id === 'quiz') {
            $data = (clone $dataBuild)->where('cfmt_quiz_flag', 1)->get();
        } else {
            $data = (clone $dataBuild)->where('cfmt_quiz_flag', 0)->get();
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
        //
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
}
