<?php

namespace App\Models\HRMS\Core\Form;

use Illuminate\Database\Eloquent\Model;
use Awobaz\Compoships\Compoships;

class FormDet extends Model
{
    use Compoships;
    
    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_form_mstr_det';
    protected $fillable = [
        'form_mstr_id',
        'form_var_id',
        'form_option_var',
        'form_option_label'
    ];
}
