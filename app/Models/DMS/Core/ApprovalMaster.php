<?php

namespace App\Models\DMS\Core;

use Illuminate\Database\Eloquent\Model;

class ApprovalMaster extends Model
{
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_apprv_mstr';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'apprv_author',
        'apprv_approver',
        'apprv_email_notify',
        'apprv_level',
        'apprv_mandatory',
        'apprv_title',
        'apprv_id'
    ];

    public function user()
    {
        return $this->hasOne('App\Models\DMS\Auth\UsersMaster','username','apprv_approver');
    }

    public function userAuthor()
    {
        return $this->hasOne('App\Models\DMS\Auth\UsersMaster','username','apprv_author');
    }

    public function hist()
    {
        return $this->hasMany('App\Models\DMS\Core\ApprovalHist','id_approval','apprv_id');
    }

    public function histByApprover()
    {
        return $this->hasMany('App\Models\DMS\Core\ApprovalHist','apprv_hist_user','apprv_approver');
    }

    public function contentDef()
    {
        return $this->hasMany('App\Models\DMS\Core\ContentDefine','apprv_mstr_id','apprv_id');
    }

    public function approvalDocSet()
    {
        return $this->hasMany('App\Models\DMS\Core\DocApprovalSet','approval_id','apprv_id');
    }
}
