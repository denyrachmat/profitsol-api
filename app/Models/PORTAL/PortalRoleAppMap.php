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
        'am_app_id',
        'am_app_parent',
    ];

    public function apps()
    {
        return $this->hasOne('App\Models\PORTAL\PortalApp', 'am_app_code', 'am_app_id');
    }

    public function role()
    {
        return $this->hasOne('App\Models\PORTAL\PortalRole', 'id', 'rm_role_id');
    }

    public function child()
    {
        return $this->hasMany('App\Models\PORTAL\PortalRoleAppMap','am_app_parent','am_app_id');
    }

    public function childRoles()
    {
        return $this->child()->with('childRoles')->with('apps')->orderBy('am_app_id');
    }
}
