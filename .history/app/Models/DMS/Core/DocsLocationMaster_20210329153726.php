<?php

namespace App\Models\DMS\Core;

use Illuminate\Database\Eloquent\Model;

class DocsLocationMaster extends Model
{
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_locdoc_mstr';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'parent_loc',
        'name_loc',
        'creator_loc'
    ];

    public function getParents()
    {
        return $this->hasMany('App\Models\DMS\Core\DocsLocationMaster','parent_loc', 'id');
    }

    public function getChild()
    {
        return $this->hasOne('App\Models\DMS\Core\DocsLocationMaster','id', 'parent_loc');
    }

    public function allChildFolder()
    {
        return $this->getParents()->with('allChildFolder')->with('allParentFolder');
    }

    public function allParentFolder()
    {
        return $this->getChild()->with('allParentFolder');
    }

    public function getSharedFolder()
    {
        return $this->hasMany('App\Models\DMS\Core\sharedMapping', 'shared_to', 'creator_loc');
    }

    public function users()
    {
        return $this->hasOne('App\Models\DMS\Auth\UsersMaster','username','creator_loc');
    }

    public function listDoc()
    {
        return $this->hasMany('App\Models\DMS\Core\DocsMaster','doc_path','id');
    }
}
