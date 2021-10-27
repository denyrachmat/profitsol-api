<?php

namespace App\Models\DMS\Core;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\CompositeKey;

class tagsDocDet extends Model
{
    use CompositeKey;
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_tags_docs_det';

    protected $fillable = [
        'tags_id',
        'doc_id'
    ];

    protected $primaryKey = [
        'tags_id',
        'doc_id'
    ];

    public function tagsMaster()
    {
        return $this->hasOne('App\Models\DMS\Core\tagsMaster','id','tags_id');
    }
}
