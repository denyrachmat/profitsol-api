<?php

namespace App\Models\PORTAL;

use Illuminate\Database\Eloquent\Model;

class RolesPortal extends Model
{
    protected $table = 'psi_portal_role';

    public function menu()
    {
        return $this->hasOne('App\Models\PORTAL\MenusPortal','id','menu_id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\PORTAL\UsersPortal','role_identifier','role_id');
    }

    public function group()
    {
        return $this->hasOne('App\Models\PORTAL\DivisisPortal','role_id','role_identifier');
    }
}
