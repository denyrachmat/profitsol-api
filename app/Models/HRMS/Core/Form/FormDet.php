<?php

namespace App\Models\HRMS\Core\Form;

use Illuminate\Database\Eloquent\Model;
use Awobaz\Compoships\Compoships;
use App\Helpers\CompositeKey;

class FormDet extends Model
{
    use Compoships, CompositeKey;
    
    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_form_mstr_det';

    protected $primaryKey = [
        'form_mstr_id',
        'form_var_id'
    ];

    protected $fillable = [
        'form_mstr_id',
        'form_var_id',
        'form_option_var',
        'form_option_label'
    ];
}
