<?php

namespace App\Http\Controllers\API\CMS;

use App\Http\Controllers\Controller;
use App\Models\CMS\FormAnswerDet;
use App\Models\CMS\FormMultiDet;
use Illuminate\Http\Request;
use App\Traits\CMS\FormsTraits;
use App\Models\CMS\FormAnswerUserDet;
use App\Models\CMS\FormMasterTitle;
use App\Models\CMS\FormSetupDet;
use App\Models\CMS\FormMaster;
use App\Models\CMS\FormShareDet;
use App\Traits\TOS\TrainingTraits;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
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

        // return $request;
        FormAnswerUserDet::where('cfm_id', $request->id)
            ->where('p_u_username', $request->header('username'))
            ->delete();
        $created = [];
        foreach ($request->ans as $key => $value) {
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

            $created[] = FormAnswerUserDet::create([
                'p_u_username' => $request->header('username'),
                'cfaud_batch' => $nextID,
                'cfm_id' => $request->id,
                'cfmd_id' => $request->questId[$key],
                'cfm_val' => is_array($value) ? json_encode(sort($value)) : $value,
            ]);
        }

        return response([
            'status' => true,
            'message' => 'Answers successfully submited !',
            'data' => $created
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id, $idDet = '')
    {
        $dataAnswers = FormAnswerDet::where('cfm_id', $id)->get();
        $dataHeader = FormMasterTitle::where('id', $id)->with([
            'formMaster' => function ($f2) {
                $f2->where('cfm_parent_id', 0);
                $f2->with('formDetail.formAnswer');
                $f2->with('allChildrenContent');
            }
        ])->first();

        $hasil = [];
        foreach ($dataAnswers as $key => $value) {
            $answers = is_array(json_decode($value['cfm_val'])) ? json_decode($value['cfm_val']) : $value['cfm_val'];

            $data = FormAnswerUserDet::where('p_u_username', $request->header('username'))->where('cfm_id', (int)$id)->where('cfmd_id', (int)$value['cfmd_id'])->first();

            $answersUser = !empty($data)
                ? (is_array(json_decode($data->cfm_val)) ? json_decode($data->cfm_val) : $data->cfm_val)
                : (is_array(json_decode($value['cfm_val'])) ? [] : "");

            $getLabelCek = FormMultiDet::select('cfmd_label')
                ->where('cfm_id', $data->cfmd_id)
                ->whereIn('cfmd_value', is_array(json_decode($value['cfm_val'])) ? json_decode($value['cfm_val']) : [$value['cfm_val']]);

            if (!empty($idDet)) {
                $getLabelCek->where('cfmd_id', $idDet);
            }

            $getLabel = $getLabelCek->pluck('cfmd_label');

            $hasil[$key] = [
                'status' => is_array($answers) ? sort($answers) : $answers === $answersUser,
                'users' => $answersUser,
                'ans' => $answers,
                'ans_value' => $getLabel,
                'exp' => $value['cfm_exp']
            ];
        }

        $getGrade = array_filter($hasil, function ($f) {
            return $f['status'];
        });

        $totalGrade = round((count($getGrade) / count($dataAnswers)) * 100, 2);
        $cekStatGrade = FormSetupDet::where('cfmt_id', $id)->first();

        return response([
            'status' => true,
            'data' => $hasil,
            'grade' => $totalGrade,
            'is_pass' => $totalGrade >= $cekStatGrade['cfsd_min_pass'],
            'data_ori' => $this->getHeaderAllForms([$dataHeader->toArray()])[0]['forms']
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

    public function migrationHRMS()
    {
        $data = DB::select("
            SELECT 
                '' as id,
                'deny-rachmat@sumitronics.co.jp' as p_u_username,
                cfmt.id as cfmt_id,
                'form' as cfm_type,
                hfcm.div_content,
                (
                    select count(*) from HRMS.dbo.hrms_form_mstr_det hfmd
                    where hfmd.form_mstr_id = hfcm.div_content
                ) as totForms
            FROM STX_CMS.dbo.cms_form_mstr_title cfmt
            INNER JOIN HRMS.dbo.hrms_form_content_mapping hfcm ON hfcm.form_name = cfmt.cfmt_title
            where cfmt.cfmt_quiz_flag = 1
            --and hfcm.div_id = 'FRM-2209120002'
            order by hfcm.id
        ");

        $hasil = [];
        foreach ($data as $key => $value) {
            $getAnswersTest = DB::table('HRMS.dbo.hrms_form_key_answers as hfka')
            ->where('hfka.form_id', $value->div_content)
            ->get();

            $hasilConvert = [
                'component' => [
                    'label' => count($getAnswersTest) > 1 ? 'Multiple Checkbox (Multiple choice)' : 'Multiple Choice',
                    'category' => 'multiple',
                    'value' => [
                        'type' => count($getAnswersTest) > 1 ? 'multiple-checkbox' : 'multiple-radio',
                        'comp' => count($getAnswersTest) > 1 ? 'q-checkbox' : 'q-radio'
                    ]
                ],
                'detail_data' => [],
                'label' => ''
            ];
            if ($value->totForms > 0) {
                $dataDet = DB::table('HRMS.dbo.hrms_form_mstr_det as hfmd')->where('hfmd.form_mstr_id', $value->div_content)->get();
                $getLabel = DB::table('HRMS.dbo.hrms_form_mstr as hfm')->where('hfm.form_id', $value->div_content)->first();
                foreach ($dataDet as $keyDet => $valueDet) {
                    $hasilConvert['detail_data'][] = [
                        'col_det_id' => $valueDet->form_var_id,
                        'col_det_label' => '',
                        'value' => $keyDet + 1,
                        'label' => $valueDet->form_option_label
                    ];
                }

                $hasilConvert['label'] = $getLabel->form_label;
            }

            $hasil[] = [
                'ori_id' => $value->div_content,
                'id' => $value->id,
                'p_u_username' => $value->p_u_username,
                'cfmt_id' => $value->cfmt_id,
                'cfm_type' => $value->totForms > 0 ? 'form' : 'html',
                'cfm_seq_name' => null,
                'cfm_content' => $value->totForms > 0 ? $hasilConvert : $value->div_content,
                'cfm_parent_id' => 0,
                'cfm_required' => 0
            ];
        }

        foreach ($hasil as $keyHasil => $valueHasil) {
            if ($valueHasil['cfm_type'] === 'form') {
                $master = FormMaster::create([
                    'p_u_username' => $valueHasil['p_u_username'],
                    'cfmt_id' => $valueHasil['cfmt_id'],
                    'cfm_type' => $valueHasil['cfm_type'],
                    'cfm_seq_name' => $valueHasil['cfm_seq_name'],
                    'cfm_content' => is_array($valueHasil['cfm_content']) ? json_encode($valueHasil['cfm_content']) : $valueHasil['cfm_content'],
                    'cfm_parent_id' => $valueHasil['cfm_parent_id'],
                    'cfm_required' => $valueHasil['cfm_required'],
                ]);
                
                $getLabelList = [];
                foreach ($valueHasil['cfm_content']['detail_data'] as $key => $valueContent) {
                    $insertFormDet = FormMultiDet::create([
                        'cfm_id' => $master->id,
                        'cfmd_value' => $valueContent['value'],
                        'cfmd_label' => $valueContent['label'],
                    ]);

                    $getLabelList[]['value'] = $valueContent['value'];
                    $getLabelList[]['label'] = $valueContent['label'];
                }

                $getAnswers = DB::table('HRMS.dbo.hrms_form_key_answers as hfka')
                    ->where('hfka.form_id', $valueHasil['ori_id'])
                    // ->where('hfka.ans_val', 'like', '%'.$valueContent['label'].'%')
                    ->get();

                if (count($getAnswers) > 0) {
                    $getAnswersFilter = array_values(array_filter($getAnswers->toArray(), function ($f) use ($valueHasil) {
                        $listLabel=[];
                        foreach ($valueHasil['cfm_content']['detail_data'] as $key => $valueLabelDet) {
                            $listLabel[] = $valueLabelDet['label'];
                        }

                        return in_array($f->ans_val, $listLabel);
                    }));
                    
                    if (count($getAnswersFilter) === 1) {
                        FormAnswerDet::create([
                            'p_u_username' => 'deny-rachmat@sumitronics.co.jp',
                            'cfm_id' => $valueHasil['cfmt_id'],
                            'cfmd_id' => $master->id,
                            'cfm_val' => FormMultiDet::where(DB::raw('CAST(cfmd_label AS VARCHAR(MAX))'), $getAnswersFilter[0]->ans_val)->first()->cfmd_value,
                            'cfm_exp' => !empty($getAnswersFilter[0]->ans_remark) ? $getAnswersFilter[0]->ans_remark : ''
                        ]);
                    } else {

                        $hasilValAns = [];
                        foreach ($getAnswersFilter as $keyAns => $valueAns) {
                            $hasilValAns[] = $valueAns->ans_val;
                        }

                        FormAnswerDet::create([
                            'p_u_username' => 'deny-rachmat@sumitronics.co.jp',
                            'cfm_id' => $valueHasil['cfmt_id'],
                            'cfmd_id' => $master->id,
                            'cfm_val' => json_encode(FormMultiDet::whereIn(DB::raw('CAST(cfmd_label AS VARCHAR(MAX))'), $hasilValAns)->get()->pluck('cfmd_value')),
                            'cfm_exp' => !empty($getAnswersFilter[0]->ans_remark) ? $getAnswersFilter[0]->ans_remark : ''
                        ]);
                    }
                }
            }
        }

        return $hasil;
    }

    public function migrateUsersAnswers() {
        ini_set('memory_limit', '2G');
        $getUsers = DB::table('HRMS.dbo.hrms_user_mstr as hum')
            ->select('hum.email', 'hum.first_name', 'hum.last_name', 'hum.username')
            ->join(DB::raw('HRMS.dbo.hrms_form_hist hfh'), 'hum.username', 'hfh.form_hist_username')
            ->join(DB::raw('HRMS.dbo.hrms_form_content_mapping hfcm'), 'hfcm.div_id', 'hfh.form_id')
            //->where('hum.email', 'muhammad-zubir@sumitronics.co.jp')
            ->groupBy('hum.email', 'hum.first_name', 'hum.last_name', 'hum.username')
            ->get();

        // return $getUsers;
        // Migrate users

        $hasil = [];
        foreach ($getUsers as $key => $value) {
            $userCheck = User::where('email', $value->email)->first();
            if (empty($userCheck)) {
                $user = User::create([
                    'username' => $value->email,
                    'email' => $value->email,
                    'email_verified_at' => date('Y-m-d H:i:s'),
                    'password' => bcrypt('123456'),
                ]);

                $user->det()->create([
                    'u_username' => $value->email,
                    'pud_first_name' => $value->first_name,
                    'pud_last_name' => $value->last_name,
                ]);
            } else {
                $user = $userCheck;
            }

            $dataJawabanDraft = DB::table('HRMS.dbo.hrms_user_mstr as hum')
                ->join(DB::raw('HRMS.dbo.hrms_form_hist hfh'), 'hum.username', 'hfh.form_hist_username')
                ->join(DB::raw('HRMS.dbo.hrms_form_content_mapping hfcm'), function ($j) {
                    $j->on('hfh.form_id', 'hfcm.div_id');
                    $j->on('hfh.form_hist_id', 'hfcm.div_content');
                })
                ->join(DB::raw('STX_CMS.dbo.cms_form_mstr_title cfmt'), 'cfmt.cfmt_title', 'hfcm.form_name')
                ->where('hfh.form_hist_username', $value->username);
            
            $dataJawaban = (clone $dataJawabanDraft)
                ->select(
                    'hum.*',
                    'hfh.*',
                    'hfcm.form_name',
                    'cfmt.id as cfmt_id'
                )
                // ->where('hfh.form_id', 'FRM-2209120002')
                ->orderBy('hfh.created_at')
                ->get();

            $userAns = [];
            foreach ($dataJawaban as $key => $valueJawaban) {
                $cekIDJawaban = FormMultiDet::whereIn(DB::raw('CAST(cfmd_label AS VARCHAR(MAX))'), json_decode($valueJawaban->form_hist_value))
                ->orderBy('cfm_id', 'asc');

                $getIDJawaban = (clone $cekIDJawaban)->get()->pluck('cfmd_value');
                $getIDJawabanAll = (clone $cekIDJawaban)->get();

                // FormAnswerUserDet::where('p_u_username', $valueJawaban->email)
                //     ->where('cfm_id', $getIDJawabanFirst->cfm_id)
                //     ->where('cfmd_id', $getIDJawabanFirst->cfmd_id)
                //     ->forceDelete();

                $groupJawaban = [];
                foreach ($getIDJawaban as $key => $value) {
                    $groupJawaban[$value] = $value;
                }

                if (count($getIDJawabanAll) > 0) {
                    $cekJawabanExists = FormAnswerUserDet::where('cfm_id',$valueJawaban->cfmt_id)
                        // ->whereIn('cfmd_id', (clone $getIDJawabanAll)->pluck('cfm_id'))
                        ->where('p_u_username', $valueJawaban->email)
                        // ->where('cfm_val', count(array_values($groupJawaban)) > 1 ? json_encode(array_values($groupJawaban)) : array_values($groupJawaban)[0])
                        ->get()
                        ->pluck('cfmd_id')
                        ->toArray();
                    
                    $viewDataJawaban = (clone $getIDJawabanAll)->pluck('cfm_id')->toArray();
                    $filterDataAnsExists = array_values(array_filter($viewDataJawaban, function($f) use ($cekJawabanExists) {
                        return !in_array($f, $cekJawabanExists);
                    }));

                    if (count($filterDataAnsExists) > 0) {    
                        if (count($filterDataAnsExists) > 1) {
                            // logger([$valueJawaban->cfmt_id, $viewDataJawaban, $cekJawabanExists, $filterDataAnsExists]);
                        }

                        $cekBatch = FormAnswerUserDet::select('cfaud_batch')->where('p_u_username',$valueJawaban->email)
                            ->where('cfm_id', $valueJawaban->cfmt_id)
                            ->where('cfmd_id', $filterDataAnsExists[0])
                            ->withTrashed()
                            ->first();

                        $cekLatestBatch = FormAnswerUserDet::select('cfaud_batch')->where('p_u_username',$valueJawaban->email)
                        ->where('cfm_id', $valueJawaban->cfmt_id)
                        ->first();

                        $userAja = FormAnswerUserDet::create([
                            'p_u_username' => $valueJawaban->email,
                            'cfm_id' => $valueJawaban->cfmt_id,
                            'cfmd_id' => $filterDataAnsExists[0],
                            'cfm_val' => count(array_values($groupJawaban)) > 1 ? json_encode(array_values($groupJawaban)) : array_values($groupJawaban)[0],
                            'cfaud_batch' => empty($cekBatch) ? (empty($cekLatestBatch) ? 1 : $cekLatestBatch->cfaud_batch) : $cekBatch->cfaud_batch + 1
                        ]);
        
                        if (!empty($valueJawaban->deleted_at)) {
                            FormAnswerUserDet::where('p_u_username', $valueJawaban->email)
                                ->where('cfm_id', $valueJawaban->cfmt_id)
                                ->where('cfmd_id', $filterDataAnsExists[0])
                                ->where('cfm_val',count(array_values($groupJawaban)) > 1 ? json_encode(array_values($groupJawaban)) : array_values($groupJawaban)[0])
                                ->delete();
                        } else {
                            $userAns[] = $userAja;
                        }
                    }
                }
            }

            $dataJawabanForRegUserShare = (clone $dataJawabanDraft)
                ->select(
                    'cfmt.id as cfmt_id'
                )
                ->groupBy('cfmt.id')
                ->get();

            foreach ($dataJawabanForRegUserShare as $key => $valueReg) {
                $cekData = FormShareDet::where('cfmt_id', $valueReg->cfmt_id)
                    ->where('cfsd_to', $user->username)
                    ->first();

                if (empty($cekData)) {
                    FormShareDet::create([
                        'cfmt_id' => $valueReg->cfmt_id,
                        'p_u_username' => 'deny-rachmat@sumitronics.co.jp',
                        'cfsd_to' => $user->username,
                        'cfsd_gen_link' => \Illuminate\Support\Str::random(40)
                    ]);
                }
            }

            foreach ($userAns as $keyAnsRevision => $valueAnsRevision) {
                $cekJawaban = FormAnswerDet::where('cfm_id', $valueAnsRevision->cfm_id)
                    ->where('cfmd_id', $valueAnsRevision->cfmd_id)
                    // ->where('p_u_username', $valueAnsRevision->p_u_username)
                    ->first();

                // logger($cekJawaban);

                if (!empty($cekJawaban)) {
                    FormAnswerUserDet::where('cfm_id', $valueAnsRevision->cfm_id)
                        ->where('cfmd_id', $valueAnsRevision->cfmd_id)
                        ->where('p_u_username', $valueAnsRevision->p_u_username)
                        ->update([
                            'cfm_val' => $cekJawaban->cfm_val
                        ]);
                }
            }

            $hasil[] = [
                'data' => $dataJawaban,
                'insertAns' => $userAns
            ];
        }

        return $hasil;
    }
}