<?php

namespace App\Models\DMS\Core;

use Illuminate\Database\Eloquent\Model;

class DocApprovalSet extends Model
{
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_doc_approval';

    protected $fillable = [
        "doc_id",
        "approval_id",
    ];

    public function doc()
    {
        return $this->hasOne('App\Models\DMS\Core\DocsMaster','doc_id','doc_id');
    }

    public function docHist()
    {
        return $this->hasMany('App\Models\DMS\Core\ApprovalHist','apprv_hist_doc','doc_id');
    }
}
