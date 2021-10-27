<?php

namespace App\Models\DMS\Core;

use Illuminate\Database\Eloquent\Model;

class tagsMaster extends Model
{
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_tags_mster';

    protected $fillable = [
        'tags_name',
        'tags_desc'
    ];
}
