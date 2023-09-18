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
                DB::raw("
                    CASE WHEN SUM(cfaud.cfm_val) > 0
                        THEN ((SUM(cfaud.cfm_val) / MAX(cfaud.tot_question)) * 100)
                        ELSE 0
                    END AS cfm_val
                "),
                DB::raw('count(cfaud.cfaud_batch) as tot_try'),
                DB::raw('MAX(cfaud.created_at) as last_answers'),
                DB::raw('MIN(cfaud.created_at) as first_answers'),
                'cfsd.cfsd_start_quiz',
                'cfsd.cfsd_end_quiz',
                'cfsd.cfsd_timer',
                // DB::raw('CAST((CAST((COALESCE(SUM(cfaud.cfm_val), 0)) as decimal(12,2)) / MAX(cfaud.tot_question)) * 100 AS DECIMAL(12,2)) as cfm_val'),
                DB::raw('MAX(cfaud.tot_question) as tot_question'),
                DB::raw("
                    CASE WHEN SUM(cfaud.cfm_val) > 0 AND ((SUM(cfaud.cfm_val) / MAX(cfaud.tot_question)) * 100) >= cfsd.cfsd_min_pass
                        THEN 'PASSED'
                        ELSE 'NOT PASSED'
                    END AS status
                "),
                DB::raw("SUM(cfaud.cfm_val) as total_answer")
            )
            ->join(DB::raw('cms_form_share_det cfsd2'), 'cfsd2.cfmt_id','cfmt.id')
            ->where('cfmt_quiz_flag', 1)
            ->where('cfsd2.cfsd_to', $username);
            //->where('cfmt.p_u_username', $username);

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
                    cfaud.p_u_username,
                    MAX(cfaud.deleted_at) as deleted_at,
                    MAX(cfaud.created_at) as created_at,
                    SUM(CASE WHEN cast(cfad.cfm_val as varchar(max)) = cast(cfaud.cfm_val as varchar(max)) and cfaud.deleted_at is null
                        then 1
                        else 0
                    end) as cfm_val,
                    count(cfad.id) as tot_question
                FROM cms_form_ans_user_det cfaud
                LEFT JOIN cms_form_ans_det cfad ON cfaud.cfm_id = cfad.cfm_id
                    AND cfaud.cfmd_id = cfad.cfmd_id
                    AND cfaud.deleted_at is null
                --WHERE cfaud.deleted_at is null
                --AND cfaud.cfaud_batch is not null
                GROUP BY
                    cfaud.cfm_id,
                    cfaud.p_u_username,
                    cfaud.cfaud_batch
            ) as cfaud'), function($j) {
                $j->on('cfmt.id', 'cfaud.cfm_id');
                $j->on('cfsd2.cfsd_to', 'cfaud.p_u_username');
            })
            ->leftjoin('cms_form_setup_det as cfsd', 'cfmt.id', 'cfsd.cfmt_id')
            ->groupBy(
                'cfmt.id',
                'cfmt_title',
                'cfsd.cfsd_start_quiz',
                'cfsd.cfsd_end_quiz',
                'cfsd.cfsd_min_pass',
                'cfsd.cfsd_timer',
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