<?php

namespace App\Models\DMS\Core;

use Illuminate\Database\Eloquent\Model;

class DocsMaster extends Model
{
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_doc_mstr';
    protected $primaryKey = 'doc_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'doc_id',
        'doc_name',
        'doc_path',
        'doc_real_path',
        'doc_author',
        'doc_real_name',
        'doc_size',
        'doc_lapprv_flag',
        'doc_stat_flag'
    ];

    public function version()
    {
        return $this->hasOne('App\Models\DMS\Core\VerMaster','ver_docloc','doc_id');
    }

    public function currentversion()
    {
        return $this->hasOne('App\Models\DMS\Core\VerMaster','ver_docnm','doc_id');
    }

    public function users()
    {
        return $this->hasOne('App\Models\DMS\Auth\UsersMaster','username','doc_author');
    }

    public function apprvhist()
    {
        return $this->belongsTo('App\Models\DMS\Core\ApprovalHist','doc_id','apprv_hist_doc')->orderBy('approver_level')->withTrashed();
    }
    
    public function apprvhistlast()
    {
        return $this->belongsTo('App\Models\DMS\Core\ApprovalHist','doc_id','apprv_hist_doc')->orderBy('approver_level','desc');
    }

    public function docHist()
    {
        return $this->hasMany('App\Models\DMS\Core\ApprovalHist','apprv_hist_doc','doc_id');
    }

    public function cirten()
    {
        return $this->hasOne('App\Models\DMS\Custom\CircularTen','doc_id','doc_id');
    }
}
