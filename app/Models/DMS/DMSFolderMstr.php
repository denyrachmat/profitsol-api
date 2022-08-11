<?php

namespace App\Models\DMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DMSFolderMstr extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_folder_mstr';

    protected $fillable = [
        'p_u_username',
        'dfm_folder_name',
        'dfm_parent_id'
    ];

    public function doc()
    {
        return $this->hasMany('App\Models\DMS\DMSDocMstr', 'dfm_id', 'id');
    }

    public function child()
    {
        return $this->hasMany('App\Models\DMS\DMSFolderMstr','dfm_parent_id','id');
    }

    public function childFolders()
    {
        return $this->child()->with('childFolders')->with('doc');
    }
}
