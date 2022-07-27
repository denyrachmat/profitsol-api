<?php

namespace App\Models\PORTAL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalRole extends Model
{
    use HasFactory;

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

    public function app_map()
    {
        return $this->hasMany('App\Models\PORTAL\PortalRoleAppMap', 'rm_role_id', 'id');
    }
}
