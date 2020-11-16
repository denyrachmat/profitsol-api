<?php

namespace App\Models\HRMS\Core\Form;

use Illuminate\Database\Eloquent\Model;

class FormPageMappingDet extends Model
{
    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_form_page_mapping_det';

    protected $fillable = [
        'mapping_id',
        'domain_id',
        'division_id',
        'username'
    ];
}
