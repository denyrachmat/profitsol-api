<?php

namespace App\Models\DMS\Core;

use Illuminate\Database\Eloquent\Model;

class ContentDet extends Model
{
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_content_det';

    protected $fillable = [
        'content_mstr_id',
        'content_var'
    ];
}
