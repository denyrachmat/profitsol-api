<?php

namespace App\Models\DMS\Core;

use Illuminate\Database\Eloquent\Model;

class ContentCreator extends Model
{
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_content_creator';

    protected $fillable = [
        'content_var_id',
        'content_var_value',
        'content_users',
        'content_creator_id',
    ];
}
