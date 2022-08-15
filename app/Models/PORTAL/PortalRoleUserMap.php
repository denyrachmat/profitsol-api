<?php

namespace App\Models\PORTAL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalRoleUserMap extends Model
{
    use HasFactory;

    protected $table = 'portal_role_users_map';
    protected $fillable = [
        'u_username',
        'rm_role_id'
    ];

    public function users()
    {
        return $this->hasOne('App\Models\user', 'u_username', 'username');
    }

    public function role()
    {
        return $this->hasOne('App\Models\PORTAL\PortalRole', 'id', 'rm_role_id');
    }
}
