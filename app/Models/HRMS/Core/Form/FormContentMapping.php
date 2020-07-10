<?php

namespace App\Models\HRMS\Core\Form;

use Illuminate\Database\Eloquent\Model;

class FormContentMapping extends Model
{
    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_form_content_mapping';
    protected $fillable = [
        'div_id',
        'form_name',
        'content_id',
        'div_content',
        'div_type',
        'div_username'
    ];
}
