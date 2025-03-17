<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormSetupDet extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_cms';
    protected $table = 'cms_form_setup_det';

    protected $fillable = [
        'cfmt_id',
        'cfsd_res_show',
        'cfsd_ans_show',
        'cfsd_rand_quest',
        'cfsd_ans_loc',
        'cfsd_timer',
        'cfsd_timer_quest',
        'cfsd_hours',
        'cfsd_min',
        'cfsd_sec',
        'cfsd_min_pass',
        'cfsd_start_quiz',
        'cfsd_end_quiz',
        'cfsd_real_start_quiz',
        'cfsd_real_end_quiz',
        'cfsd_quest_limit',
        'cfsd_skip_next_btn_media_done',
    ];
}
