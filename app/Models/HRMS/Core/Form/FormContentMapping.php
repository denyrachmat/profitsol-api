<?php

namespace App\Models\HRMS\Core\Form;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\CompositeKey;

class FormContentMapping extends Model
{
    use CompositeKey;

    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_form_content_mapping';
    protected $fillable = [
        'div_id',
        'form_name',
        'row_id',
        'col_id',
        'div_content',
        'div_type',
        'div_username',
        'page_id',
        'content_parent'
    ];

    protected $primaryKey = [
        'div_id',
        'row_id',
        'col_id'
    ];

    public function divRelation()
    {
        return $this->hasOne('App\Models\HRMS\Core\Form\FormMaster','form_id','div_content');
    }

    public function formLogics()
    {
        return $this->hasMany('App\Models\HRMS\Core\Form\FormLogicsMstr','content_id','div_id');
    }

    public function child()
    {
        return $this->hasMany('App\Models\HRMS\Core\Form\FormContentMapping','content_parent','col_id');
    }

    public function formMappingChild()
    {
        return $this->child()->with('formMappingChild');
    }

    public function pageMapping()
    {
        return $this->hasOne('App\Models\HRMS\Core\Form\FormPageMapping','content_id','div_id');
    }
}
