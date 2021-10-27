<?php

namespace App\Models\DMS\Core;

use Illuminate\Database\Eloquent\Model;

class tagsDocDet extends Model
{
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_tags_docs_det';

    protected $fillable = [
        'tags_id',
        'doc_id'
    ];

    public function tagsMaster()
    {
        return $this->hasMany('App\Models\DMS\Core\tagsMaster','id','tags_id');
    }
}
