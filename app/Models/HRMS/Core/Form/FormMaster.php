<?php

namespace App\Models\HRMS\Core\Form;

use Illuminate\Database\Eloquent\Model;
use Awobaz\Compoships\Compoships;
use App\Helpers\CompositeKey;

class FormMaster extends Model
{
    use Compoships, CompositeKey;

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

    protected $primaryKey = [
        'form_id'
    ];

    public function formDet()
    {
        return $this->hasMany('App\Models\HRMS\Core\Form\FormDet', 'form_mstr_id', 'form_id');
    }

    public function formAnswersDet()
    {
        return $this->hasMany('App\Models\HRMS\Core\Form\FormAnswersDet', 'form_id', 'form_id');
    }

    public function formLogics()
    {
        return $this->hasMany('App\Models\HRMS\Core\Form\FormLogicsMstr','form_content_id','form_id');
    }

    public function formHist()
    {
        return $this->hasMany('App\Models\HRMS\Core\Form\FormHist','form_hist_id','form_id');
    }
}
