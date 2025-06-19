<?php

namespace App\Models\PORTAL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalRPAParamDet extends Model
{
    use HasFactory;
    protected $table = 'portal_rpa_param_det';
    protected $fillable = [
        'prpd_prmid',
        'prpd_param_name',
        'prpd_param_required',
        'prpd_param_type',
        'prpd_param_desc',
        'prpd_param_default',
        'prpd_isactive'
    ];
    protected $casts = [
        'prpd_param_required' => 'boolean',
        'prpd_isactive' => 'boolean',
    ];
}
