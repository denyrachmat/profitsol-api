<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormMultiDet extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_cms';
    protected $table = 'cms_form_multi_det';

    protected $fillable = [
        'cfm_id',
        'cfmd_value',
        'cfmd_label',
    ];
}
