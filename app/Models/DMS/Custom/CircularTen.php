<?php

namespace App\Models\DMS\Custom;

use Illuminate\Database\Eloquent\Model;

class CircularTen extends Model
{
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_custom_cirten';

    protected $fillable = [
        'creator_id',
        'doc_id'
    ];
}
