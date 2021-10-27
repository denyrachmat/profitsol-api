<?php

namespace App\Models\DMS\Core;

use Illuminate\Database\Eloquent\Model;

class sharedMapping extends Model
{
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_logic_mstr';

    protected $fillable = [
        'folder_id',
        'files_id',
        'shared_to'
    ];
}
