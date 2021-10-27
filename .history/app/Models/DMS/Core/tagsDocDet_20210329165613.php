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
}
