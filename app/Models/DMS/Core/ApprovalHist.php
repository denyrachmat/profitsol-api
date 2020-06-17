<?php

namespace App\Models\DMS\Core;

use Illuminate\Database\Eloquent\Model;
use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalHist extends Model
{
    use Compoships;
    use SoftDeletes;

    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_apprv_hist';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'apprv_parent',
        'apprv_hist_user',
        'apprv_hist_doc',
        'apprv_hist_comment',
        'apprv_hist_status',
        'apprv_hist_vwtime',
        'content_creator_id',
        'id_approval',
        'content_def_id',
        'approver_level'
    ];

    public function doc()
    {
        return $this->hasOne('App\Models\DMS\Core\DocsMaster', 'doc_id', 'apprv_hist_doc');
    }

    public function child()
    {
        return $this->hasOne('App\Models\DMS\Core\ApprovalHist', 'apprv_parent', 'id');
    }

    public function lastapprv()
    {
        return $this->hasOne('App\Models\DMS\Core\ApprovalHist', 'apprv_hist_doc', 'apprv_hist_doc')->orderBy('created_at','desc');
    }

    public function parent()
    {
        return $this->hasOne('App\Models\DMS\Core\ApprovalHist', 'id', 'apprv_parent');
    }

    public function allApproverList()
    {
        return $this->child()->with('allApproverList')->with('users');
    }

    public function allPastApproverList()
    {
        return $this->parent()->with('allPastApproverList')->with('users');
    }

    public function getallapprover()
    {
        return $this->hasMany('App\Models\DMS\Core\ApprovalMaster','apprv_id','id_approval');
    }

    public function users()
    {
        return $this->hasOne('App\Models\DMS\Auth\UsersMaster','username','apprv_hist_user');
    }

    public function content(){
        return $this->hasMany('App\Models\DMS\Core\ContentCreator','content_creator_id','content_creator_id');
    }

    public function contentVariable(){
        return $this->hasMany('App\Models\DMS\Core\ContentCreator','content_creator_id','content_creator_id');
    }

    public function contentDefine()
    {
        return $this->hasOne('App\Models\DMS\Core\ContentDefine','apprv_mstr_id','id_approval');
    }

    public function notificationFrom()
    {
        return $this->hasMany('App\Models\DMS\Core\ApprovalNotification','apprv_hist_from_id','id');
    }
}
