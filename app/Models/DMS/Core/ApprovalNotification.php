<?php

namespace App\Models\DMS\Core;

use Illuminate\Database\Eloquent\Model;
use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalNotification extends Model
{
    use Compoships;
    use SoftDeletes;
    
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_approval_notification';
    protected $fillable = [
        'apprv_user_from',
        'apprv_user_to',
        'apprv_hist_from_id',
        'apprv_hist_to_id',
        'apprv_read_flag',
        'apprv_content_id',
        'apprv_id',
        'content_def_id',
        'approver_level',
        'approver_level_to'
    ];

    public function histFromByContentId()
    {
        return $this->hasMany('App\Models\DMS\Core\ApprovalHist',['content_creator_id','apprv_hist_user','id_approval','approver_level'], ['apprv_content_id','apprv_user_from','apprv_id','approver_level']);
    }

    public function histToByContentId()
    {
        return $this->hasMany('App\Models\DMS\Core\ApprovalHist',['content_creator_id','apprv_hist_user','id_approval','approver_level'], ['apprv_content_id','apprv_user_to','apprv_id','approver_level_to']);
    }

    public function histToBySameLevel()
    {
        return $this->hasMany('App\Models\DMS\Core\ApprovalHist',['content_creator_id','id_approval','approver_level'], ['apprv_content_id','apprv_id','approver_level_to']);
    }

    public function histFrom()
    {
        return $this->hasOne('App\Models\DMS\Core\ApprovalHist','id','apprv_hist_from_id');
    }

    public function histTo()
    {
        return $this->hasOne('App\Models\DMS\Core\ApprovalHist','id','apprv_hist_to_id');
    }

    public function userFrom()
    {
        return $this->hasOne('App\Models\DMS\Auth\UsersMaster','username','apprv_user_from');
    }

    public function userTo()
    {
        return $this->hasOne('App\Models\DMS\Auth\UsersMaster','username','apprv_user_to');
    }

    public function contentVariable()
    {
        return $this->hasMany('App\Models\DMS\Core\ContentCreator','content_creator_id','apprv_content_id');
    }

    public function contentDefine()
    {
        return $this->hasOne('App\Models\DMS\Core\ContentDefine','id','content_def_id');
    }

    public function approvalMaster()
    {
        return $this->hasMany('App\Models\DMS\Core\ApprovalMaster','apprv_id','apprv_id');
    }
}
