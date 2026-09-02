<?php

namespace App\Models\PORTAL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalRole extends Model
{
    use HasFactory;
    use \Awobaz\Compoships\Compoships;

    protected $table = 'portal_role_mstr';
    protected $fillable = [
        'u_username',
        'rm_role_name',
        'rm_role_desc',
    ];

    public function users()
    {
        return $this->hasOne('App\Models\user', 'u_username', 'username');
    }

    public function users_map()
    {
        return $this->hasMany('App\Models\PORTAL\PortalRoleUserMap', 'rm_role_id', 'id');
    }

    public function role_app_map()
    {
        return $this->hasMany('App\Models\PORTAL\PortalRoleAppMap', ['rm_role_id', 'u_username'], ['id', 'u_username']);
    }

    public function role_app_map_trough()
    {
        return $this->hasManyThrough(
            'App\Models\PORTAL\PortalRoleAppMap',
            'App\Models\PORTAL\PortalRoleAppMap',
            'rm_role_id',
            'am_app_parent',
            'id',
            'am_app_id'
        );
    }
}
