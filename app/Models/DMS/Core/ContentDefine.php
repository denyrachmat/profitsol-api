<?php

namespace App\Models\DMS\Core;

use Illuminate\Database\Eloquent\Model;

class ContentDefine extends Model
{
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_content_def';

    protected $fillable = [
        'content_mstr_id',
        'apprv_mstr_id'
    ];

    public function contentDet()
    {
        return $this->hasMany('App\Models\DMS\Core\ContentDet','content_mstr_id','content_mstr_id');
    }

    public function contentMstr()
    {
        return $this->hasOne('App\Models\DMS\Core\ContentMaster','id','content_mstr_id');
    }
}
