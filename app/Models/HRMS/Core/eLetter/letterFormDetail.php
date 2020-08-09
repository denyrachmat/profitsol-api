<?php

namespace App\Models\HRMS\Core\eLetter;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\CompositeKey;

class letterFormDetail extends Model
{
    use CompositeKey;

    protected $primaryKey = [
        'id',
        'content_id',
        'form_label'
    ];

    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_content_form_det';
    protected $fillable = [
        'id',
        'content_id',
        'form_label',
        'form_type',
        'form_comp',
        'form_req',
        'form_parent',
        'username',
    ];

    public function child()
    {
        return $this->hasMany('App\Models\HRMS\Core\eLetter\letterFormDetail','form_parent','id');
    }

    public function childForm()
    {
        return $this->child()->with('childForm');
    }
}
