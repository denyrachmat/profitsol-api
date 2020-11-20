<?php

namespace App\Models\HRMS\Core\Form;

use Illuminate\Database\Eloquent\Model;

class FormAnswersDet extends Model
{
    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_form_key_answers';

    protected $fillable = [
        'form_id',
        'ans_key',
        'ans_val',
        'ans_creator',
        'ans_remark'
    ];
}
