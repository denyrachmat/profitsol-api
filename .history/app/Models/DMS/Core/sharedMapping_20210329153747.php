<?php

namespace App\Models\DMS\Core;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\CompositeKey;

class sharedMapping extends Model
{
    use CompositeKey;

    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_shared_mapping';

    protected $primaryKey = [
        'folder_id',
        'shared_to'
    ];

    protected $fillable = [
        'folder_id',
        'files_id',
        'shared_to'
    ];

    public function getChild()
    {
        return $this->hasOne('App\Models\DMS\Core\DocsLocationMaster','id', 'parent_loc');
    }

    public function allChildFolder()
    {
        return $this->getParents()->with('allChildFolder')->with('allParentFolder');
    }
}
