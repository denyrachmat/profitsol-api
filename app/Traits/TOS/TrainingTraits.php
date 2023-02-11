<?php

namespace App\Traits\TOS;

use App\Models\CMS\FormMasterTitle;
use Illuminate\Support\Facades\DB; 

trait TrainingTraits
{
    public function getTrainingList($username, $id = '')
    {
        $data = FormMasterTitle::from('cms_form_mstr_title as cfmt')
            ->select(
                'cfmt.id',
                'cfmt.cfmt_title',
                DB::raw('count(cfaud.cfaud_batch) as tot_try'),
                DB::raw('MAX(cfaud.created_at) as last_answers'),
                DB::raw('MIN(cfaud.created_at) as first_answers'),
                'cfsd.cfsd_start_quiz',
                'cfsd.cfsd_end_quiz',
                DB::raw('CAST((CAST((COALESCE(SUM(cfaud.cfm_val), 0)) as decimal(5,2)) / MAX(cfaud.tot_question)) * 100 AS DECIMAL(5,2)) as cfm_val'),
                DB::raw('MAX(cfaud.tot_question) as tot_question'),
                DB::raw("
                    CASE WHEN ((SUM(cfaud.cfm_val) / MAX(cfaud.tot_question)) * 100) >= cfsd.cfsd_min_pass
                        THEN 'PASSED'
                        ELSE 'NOT PASSED'
                    END AS status
                ")
            )
            ->where('cfmt_quiz_flag', 1)
            ->where('cfmt.p_u_username', $username);

        if (!empty($id)) {
            $data->where('cfmt.id', $id);
        }

        $data
            ->with(['formMaster' => function ($f){
                $f->where('cfm_parent_id', 0);
                $f->with('formDetail.formAnswer');
                $f->with('allChildrenContent');
            }])
            ->leftjoin(DB::raw('(
                SELECT 
                    cfaud.cfm_id,
                    cfaud.cfaud_batch,
                    MAX(cfaud.deleted_at) as deleted_at,
                    MAX(cfaud.created_at) as created_at,
                    SUM(CASE WHEN cfad.cfm_val = cfaud.cfm_val and cfaud.deleted_at is null
                        then 1
                        else 0
                    end) as cfm_val,
                    count(cfad.id) as tot_question
                FROM cms_form_ans_user_det cfaud
                LEFT JOIN cms_form_ans_det cfad ON cfaud.cfm_id = cfad.cfm_id
                    AND cfaud.cfmd_id = cfad.cfmd_id
                GROUP BY
                    cfaud.cfm_id,
                    cfaud.cfaud_batch
            ) as cfaud'), 'cfmt.id', 'cfaud.cfm_id')
            ->leftjoin('cms_form_setup_det as cfsd', 'cfmt.id', 'cfsd.cfmt_id')
            ->groupBy(
                'cfmt.id',
                'cfmt_title',
                'cfsd.cfsd_start_quiz',
                'cfsd.cfsd_end_quiz',
                'cfsd.cfsd_min_pass'
            );

        $hasil = [];
        foreach ($data->get()->toArray() as $key => $value) {
            if(count($value['form_master']) > 0) {
                $hasil[] = array_merge($value, [
                    'forms' => $this->convertToFE($value['form_master'])
                ]);
            } else {
                $hasil[] = $value;
            }
        }

        return $hasil;
    }
}