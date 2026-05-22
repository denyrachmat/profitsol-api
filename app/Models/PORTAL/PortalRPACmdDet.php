<?php

namespace App\Models\PORTAL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalRPACmdDet extends Model
{
    use HasFactory;

    protected $table = 'portal_rpa_cmd_det';
    protected $fillable = [
        'prcd_prpdid',
        'prcd_name',
        'prcd_params',
        'prcd_isactive',
        'prcd_order',
        'prcd_action',
        'prcd_parentsid',
    ];

    public function prcdParent()
    {
        return $this->hasOne(PortalRPACmdDet::class, 'id', 'prcd_parentsid');
    }

    public function prcdChildren()
    {
        return $this->hasMany(PortalRPACmdDet::class, 'prcd_parentsid', 'id');
    }
}
