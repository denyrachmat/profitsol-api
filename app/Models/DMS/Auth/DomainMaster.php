<?php

namespace App\Models\DMS\Auth;

use Illuminate\Database\Eloquent\Model;

class DomainMaster extends Model
{
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_domain_mstr';

    public function roledms()
    {
        return $this->hasMany('App\Models\DMS\Auth\RolesMaster','role_identifier','role_id');
    }

    public function userdms()
    {
        return $this->hasMany('App\Models\DMS\Auth\UsersMaster','role_id','role_id');
    }
}
