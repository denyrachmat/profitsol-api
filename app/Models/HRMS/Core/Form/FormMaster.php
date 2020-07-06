<?php

namespace App\Models\HRMS\Core\Form;

use Illuminate\Database\Eloquent\Model;
use Awobaz\Compoships\Compoships;

class FormMaster extends Model
{
    use Compoships;

    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_form_mstr';
    protected $fillable = [
        'form_id',
        'form_type',
        'form_var',
        'form_req',
        'form_username',
        'form_label'
    ];

    public function getDetail()
    {
        return $this->hasMany('App\Models\HRMS\Core\Form\FormDet', ['form_mstr_id', 'form_var_id'], ['form_id', 'form_var']);
    }
}
