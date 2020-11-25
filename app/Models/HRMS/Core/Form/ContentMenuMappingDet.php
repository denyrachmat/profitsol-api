<?php

namespace App\Models\HRMS\Core\Form;

use Illuminate\Database\Eloquent\Model;

class ContentMenuMappingDet extends Model
{
    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_content_form_det';

    protected $fillable = [
        'content_id',
        'relation_id',
    ];
}
