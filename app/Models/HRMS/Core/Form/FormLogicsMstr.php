<?php

namespace App\Models\HRMS\Core\Form;

use Illuminate\Database\Eloquent\Model;

class FormLogicsMstr extends Model
{
    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_form_logics_mstr';

    protected $fillable = [
        'content_id',
        'form_logics_id',
        'form_trigger',
        'form_logics',
        'form_content_id',
        'form_cond',
        'form_val',
        'form_actions',
        'form_order',
        'form_parent_logics_id',
    ];

    public function child()
    {
        return $this->hasMany('App\Models\HRMS\Core\Form\FormLogicsMstr','form_parent_logics_id','form_logics_id');
    }

    public function formChild()
    {
        return $this->child()->with('formChild')->with('formMaster.formDet');
    }

    public function formMaster()
    {
        return $this->hasOne('App\Models\HRMS\Core\Form\FormMaster','form_id','form_content_id');
    }
}
