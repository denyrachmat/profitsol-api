<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class FormAnswerUserDet extends Model
{
    use HasFactory, SoftDeletes;
    protected $connection = 'sqlsrv_cms';
    protected $table = 'cms_form_ans_user_det';
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'p_u_username',
        'cfaud_batch',
        'cfm_id',
        'cfmd_id',
        'cfm_val',
    ];
}
