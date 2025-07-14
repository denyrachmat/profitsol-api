<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormAMSMapDet extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_cms';
    protected $table = 'cms_form_ams_map_det';
    protected $fillable = [
        'cfamd_cfm_id',
        'cfamd_cfaud_batch',
        'cfamd_amstd_token',
        'cfamd_desc'
    ];
}
