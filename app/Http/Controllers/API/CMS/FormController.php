<?php

namespace App\Http\Controllers\API\CMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CMS\FormMaster;
use App\Models\CMS\FormMultiDet;
use App\Models\CMS\FormAnswerDet;
use App\Models\CMS\FormMasterTitle;
use App\Models\CMS\FormSetupDet;
use App\Traits\CMS\FormsTraits;
class FormController extends Controller
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
            FormMaster::where('cfmt_id', $request->idRef)->delete();
        }

        if (isset($request->setupTraining)) {
            FormSetupDet::where('cfmt_id', $request->idRef)->delete();
            FormSetupDet::create([
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
            ]);
        }

        $hasil = [];
        foreach ($data as $key => $value) {
            $hasil[] = $this->storingForms(
                $value,
                'test',
                isset($request->ans) ? $request->ans : [],
                $insertMaster->id,
                0,
                $key
            );
        }

        return response($hasil);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if ($id === 'quiz') {
            $data = formMasterTitle::with(['formMaster' => function ($f){
                $f->where('cfm_parent_id', 0);
                $f->with('formDetail.formAnswer');
                $f->with('allChildrenContent');
            }])->with('quizSetup')->where('cfmt_quiz_flag', 1)->get();
        } else {
            $data = formMasterTitle::with(['formMaster' => function ($f){
                $f->where('cfm_parent_id', 0);
                $f->with('formDetail.formAnswer');
                $f->with('allChildrenContent');
            }])->with('quizSetup')->where('cfmt_quiz_flag', 0)->get();
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
}