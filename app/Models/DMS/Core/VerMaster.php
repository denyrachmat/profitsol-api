<?php

namespace App\Models\DMS\Core;

use Illuminate\Database\Eloquent\Model;

class VerMaster extends Model
{
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_ver_mstr';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'ver_docnm',
        'ver_docloc',
        'ver_code',
        'ver_comment'
    ];

    public function doc()
    {
        return $this->hasOne('App\Models\DMS\Core\DocsMaster','doc_id','ver_docnm');
    }

    public function prevdoc()
    {
        return $this->hasOne('App\Models\DMS\Core\DocsMaster','doc_id','ver_docloc');
    }
    
    public function getchild()
    {
        return $this->hasOne('App\Models\DMS\Core\VerMaster','ver_docnm','ver_docloc');
    }

    public function prevVersion()
    {
        return $this->getchild()->with('prevVersion')->with('doc.apprvhist');
    }
}
