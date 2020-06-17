<?php

namespace App\Models\HRMS\Auth;

use Illuminate\Database\Eloquent\Model;

class RoleMaster extends Model
{
    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_role_mstr';

    public function mappingMenu()
    {
        return $this->hasMany('App\Models\HRMS\Auth\RoleMappingMenu','role_id','role_id');
    }
}
