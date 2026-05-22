<?php

namespace App\Http\Controllers\API\TOS;

use App\Http\Controllers\API\PORTAL\BaseController as BaseController;
use App\Models\CMS\FormAnswerDet;
use App\Models\CMS\FormAnswerUserDet;
use App\Models\CMS\FormMaster;
use App\Models\CMS\FormMultiDet;
use Excel;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Traits\TOS\TrainingTraits;
use App\Traits\CMS\FormsTraits;
use App\Exports\STXI\TOS\ExportListPerTraining;
use App\Exports\STXI\TOS\ExportQuestionAnalytics;

use App\Models\CMS\FormMasterTitle;

class TrainingListController extends BaseController
{
    use FormsTraits, TrainingTraits;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, $isExport = false)
    {
        set_time_limit(300);
        
        $hasil = User::select(
            'email',
            DB::raw("CONCAT(pud_first_name, ' ',ISNULL(pud_last_name, '')) as fullname"),
            DB::raw("(
                SELECT MIN(created_at) FROM STX_CMS.dbo.cms_form_ans_user_det cfaud
                WHERE cfaud.p_u_username = username
                AND cfm_id = cfmt_id
            ) as first_time_answer"),
            DB::raw("(
                SELECT MAX(created_at) FROM STX_CMS.dbo.cms_form_ans_user_det cfaud
                WHERE cfaud.p_u_username = username
                AND cfm_id = cfmt_id
            ) as last_time_answer"),
            DB::raw("(
                SELECT COALESCE(SUM(A.BATCH), 0) FROM (
                    SELECT 1 AS BATCH FROM STX_CMS.dbo.cms_form_ans_user_det cfaud
                    WHERE cfaud.p_u_username = username
                    AND cfm_id = cfmt_id
                    GROUP BY cfaud.cfaud_batch
                ) A
            ) as learn_time"),
            DB::raw('portal_role_mstr.rm_role_desc')
        )
        ->join('portal_users_det', 'username', 'u_username')
        ->join('STX_CMS.dbo.cms_form_share_det', 'username', 'cfsd_to')
        // -- Connect to Roles for temporary get division
        ->join('portal_role_users_map', 'username', 'portal_role_users_map.u_username')
        ->join('portal_role_mstr', 'portal_role_mstr.id', 'portal_role_users_map.rm_role_id')
        // -- End Connect to Roles for temporary get division
        ->whereNotNull('email_verified_at')
        ->where('rm_role_name', '<>', 'Administrator')
        ->where('cfmt_id', $id)
        ->get()
        ->toArray();

        // return $hasil;

        $hasilFinal = [];
        foreach ($hasil as $key => $value) {
            $getGrade = $this->getTrainingList($value['email'], $id);
            $hasilFinal[] = array_merge(
                $value,
                [
                    'grade' => $getGrade[0]['cfm_val'],
                    'status' => $getGrade[0]['status']
                ]
            );
        }

        return $isExport ? $hasilFinal : $this->handleResponse($hasilFinal, 'Data Found !');
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

    public function exportData($id){
        ini_set('max_execution_time', '600');
        $title = FormMasterTitle::where('cms_form_mstr_title.id', $id)->join('cms_form_setup_det', 'cms_form_mstr_title.id', 'cfmt_id')->first()->toArray();
        Excel::store(new ExportListPerTraining($this->show($id, 1), $title), $title['cfmt_title'].'-'.date('ddmmyyyy').'.xlsx', 'public');

        return '/storage/'.$title['cfmt_title'].'-'.date('ddmmyyyy').'.xlsx';
    }

    public function showHistoryPerUser($email, $id, $dataOnly = false){
        $dataAnswers = FormAnswerDet::where('cfm_id', $id)->get();
        $getBatch = FormAnswerUserDet::select(
            'cfaud_batch',
            DB::raw('MAX(created_at) as answersDate'))
        ->where('p_u_username', $email)->where('cfm_id', $id)->withTrashed()->groupBy('cfaud_batch')->get();

        // return $getBatch->toArray();
        $hasil = [];
        foreach ($getBatch as $key => $value) {
            $hasil[] = array_merge($this->dataAnswersPerUsers($dataAnswers, $email, $id, $value->cfaud_batch), ['times' => $value->answersDate, 'batch' => $value->cfaud_batch]);
        }

        if ($dataOnly) {
            return $hasil;
        }

        return $this->handleResponse($hasil, 'Data Found !!');
    }

    public function exportAnalyticsQuestion($id) {
        ini_set('max_execution_time', '600');
        $title = FormMasterTitle::where('cms_form_mstr_title.id', $id)
            ->join('cms_form_setup_det', 'cms_form_mstr_title.id', 'cfmt_id')
            ->first()
            ->toArray();

        $dataAnswersUsersOnly = FormAnswerUserDet::select('p_u_username')
            ->where('cfm_id', $id)
            ->groupBy('p_u_username')
            ->get()
            ->pluck('p_u_username');

            // return $dataAnswersUsersOnly;
        $hasilUsers = [];
        foreach ($dataAnswersUsersOnly as $keyUsers => $valueUsers) {
            $dataPerUser = $this->showHistoryPerUser($valueUsers, $id, true);

            // return $dataPerUser;
            $filterOnlyMoreThan1 = array_filter($dataPerUser, function($f) {
                return !$f['is_pass'];
            });

            // if (count($filterOnlyMoreThan1) > 0) {
            //     $hasilUsers[$valueUsers] = array_values($dataPerUser);
            // }
            $hasilUsers[$valueUsers] = array_values($dataPerUser);
        }

        $dataQuestion = array_values($hasilUsers)[0][0]['data_ori'];
        // return $dataQuestion;
        $dataFinal = [];
        foreach (array_values(array_filter($dataQuestion, function($f) {return $f['type'] === 'form';})) as $keyFinal => $valueFinal) {
            $dataQ = [];
            $dataQTrue = [];
            foreach ($hasilUsers as $keyHU => $valueHU) {
                foreach ($valueHU as $keyHUDet => $valueHUDet) {
                    $testData = array_filter($valueHUDet['data'], function($f) use ($valueFinal, $dataQTrue, $keyHU) { return !$f['status'] && $f['id'] == $valueFinal['id'] && !isset($dataQTrue[$keyHU]); });
                    $testData2 = array_filter($valueHUDet['data'], function($f, $k) use ($valueFinal, $dataQ, $keyHU) { return $f['status'] && $f['id'] == $valueFinal['id'] && !isset($dataQ[$keyHU]); }, ARRAY_FILTER_USE_BOTH);

                    if (count($testData) > 0) {
                        $dataQ[$keyHU][] = array_values($testData)[0];
                    }

                    if (count($testData2) > 0) {
                        $dataQTrue[$keyHU][] = array_values($testData2)[0];
                    }
                }
            }

            $cekAnswers = FormAnswerDet::where('cfmd_id', $valueFinal['id'])->with('answers')->first();
            $hasilAnswers = '';
            if (!empty($cekAnswers)) {
                $cekDataAns = FormMultiDet::where('cfm_id', $valueFinal['id'])
                    ->whereIn('cfmd_value', is_array(json_decode($cekAnswers->cfm_val))
                        ? json_decode($cekAnswers->cfm_val)
                        : [$cekAnswers->cfm_val])
                    ->pluck('cfmd_label')
                    ->toArray();

                $hasilAnswers = implode("\\r\\n", $cekDataAns);
            }

            // FormMultiDet::where('cfm_id', $valueFinal['id'])->first();

            $dataFinal[] = array_merge(
                $valueFinal,
                [
                    'failData' => $dataQ,
                    'successData' => $dataQTrue,
                    'answers' => $hasilAnswers,
                    'data' => json_decode($cekAnswers->cfm_val)
                ]
            );
        }

        // return $dataFinal;

        Excel::store(new ExportQuestionAnalytics($dataFinal, $title), 'analytics-'.$title['cfmt_title'].'-'.date('ddmmyyyy').'.xlsx', 'public');

        return '/storage/analytics-'.$title['cfmt_title'].'-'.date('ddmmyyyy').'.xlsx';
    }
}
