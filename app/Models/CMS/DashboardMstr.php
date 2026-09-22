<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DashboardMstr extends Model
{
    use HasFactory, SoftDeletes;
    protected $connection = 'sqlsrv_cms';
    protected $table = 'cms_dashboard_mstr';

    protected $fillable = [
        'p_u_username',
        'cdm_code',
        'cdm_title',
        'cdm_desc',
        'cdm_layout',
        'cdm_roles',
        'cdm_status',
    ];
}
