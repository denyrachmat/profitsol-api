<?php

namespace App\Models\PORTAL;

use Illuminate\Database\Eloquent\Model;

class DivisisPortal extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'psi_portal_divisi';

    public function roledms()
    {
        return $this->hasMany('App\Models\DMS\Auth\RolesMaster','role_identifier','role_id');
    }

    public function userdms()
    {
        return $this->hasMany('App\Models\DMS\Auth\UsersMaster','role_id','role_id');
    }
}
