<?php

namespace App\Http\Controllers\API\TOS;

use App\Http\Controllers\API\PORTAL\BaseController as BaseController;
use Excel;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Traits\TOS\TrainingTraits;
use App\Traits\CMS\FormsTraits;
use App\Exports\STXI\TOS\ExportListPerTraining;
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
        ->where('cfmt_id', $id)
        ->get()
        ->toArray();

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
        $title = FormMasterTitle::where('cms_form_mstr_title.id', $id)->join('cms_form_setup_det', 'cms_form_mstr_title.id', 'cfmt_id')->first()->toArray();
        Excel::store(new ExportListPerTraining($this->show($id, 1), $title), $title['cfmt_title'].'-'.date('ddmmyyyy').'.xlsx', 'public');

        return 'storage/app/public/'.$title['cfmt_title'].'-'.date('ddmmyyyy').'.xlsx';
    }
}
