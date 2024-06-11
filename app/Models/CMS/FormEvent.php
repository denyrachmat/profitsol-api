<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormEvent extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_cms';
    protected $table = 'cms_form_event';
    protected $fillable = [
        'p_u_username',
        'cfmt_id',
        'cfm_id',
        'cfe_event',
        'cfe_result',
    ];
}
