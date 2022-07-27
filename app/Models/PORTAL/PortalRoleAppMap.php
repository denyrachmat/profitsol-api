<?php

namespace App\Models\PORTAL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalRoleAppMap extends Model
{
    use HasFactory;

    protected $table = 'portal_role_app_map';
    protected $fillable = [
        'u_username',
        'rm_role_id',
        'am_app_id'
    ];

    public function apps()
    {
        return $this->hasOne('App\Models\PORTAL\PortalApp', 'am_app_code', 'am_app_id');
    }
}
