<?php

namespace App\Models\HRMS\Core\Form;

use Illuminate\Database\Eloquent\Model;

class ContentMenuMappingMstr extends Model
{
    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_content_mstr';

    protected $fillable = [
        'content_title',
        'content_html',
        'content_type',
        'content_username',
    ];
}
