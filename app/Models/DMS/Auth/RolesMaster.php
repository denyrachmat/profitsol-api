<?php

namespace App\Models\DMS\Auth;

use Illuminate\Database\Eloquent\Model;

class RolesMaster extends Model
{
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_role_mstr';

    public function menu()
    {
        return $this->hasOne('App\Models\DMS\Auth\MenusMaster','id','menu_id');
    }

    public function user()
    {
        return $this->hasMany('App\Models\DMS\Auth\UsersMaster','role_id','role_identifier');
    }

    public function group()
    {
        return $this->hasOne('App\Models\PORTAL\DivisisPortal','role_id','role_identifier');
    }
}
