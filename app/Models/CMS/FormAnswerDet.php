<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormAnswerDet extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_cms';
    protected $table = 'cms_form_ans_det';

    protected $fillable = [
        'p_u_username',
        'cfm_id',
        'cfmd_id',
        'cfm_val',
    ];
}
