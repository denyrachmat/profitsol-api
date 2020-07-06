<?php

namespace App\Models\PORTAL;

use Illuminate\Database\Eloquent\Model;

class UsersPortal extends Model
{
    protected $table = 'psi_portal_user';

    protected $fillable = [
        'username',
        'email',
        'token',
        'password_hash',
        'password_sha',
        'role_id',
        'status',
        'first_name',
        'last_name',
    ];

    public function role()
    {
        return $this->hasMany('App\Models\PORTAL\RolesPortal','role_identifier','role_id');
    }

    public function menu()
    {
        return $this->hasManyThrough(
            'App\Models\PORTAL\MenusPortal',
            'App\Models\PORTAL\RolesPortal',
            'role_identifier',
            'id',
            'role_id',
            'menu_id'
        );
    }

    public function divisi()
    {
        return $this->hasOne('App\Models\PORTAL\DivisisPortal','role_id','role_id');
    }
}
