<?php

namespace App\Models\DMS\Custom;

use Illuminate\Database\Eloquent\Model;

class ContentMappingApp extends Model
{
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_custom_content_mapping_app';

    protected $fillable = [
        'content_id',
        'app_flag',
        'app_url'
    ];
}
