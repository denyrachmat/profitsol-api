<?php

namespace App\Models\HRMS\Auth;

use Illuminate\Database\Eloquent\Model;
use Awobaz\Compoships\Compoships;

class RoleMappingMenu extends Model
{
    use Compoships;

    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_role_mapping_mstr';

    public function menu()
    {
        return $this->hasOne('App\Models\HRMS\Auth\MenuMaster','menu_order','menu_id');
    }

    public function parent()
    {
        return $this->hasOne('App\Models\HRMS\Auth\RoleMappingMenu','menu_id','parent_menu_id');
    }

    public function child()
    {
        return $this->hasMany('App\Models\HRMS\Auth\RoleMappingMenu',['parent_menu_id', 'role_id'],['menu_id', 'role_id']);
    }

    public function parentRoleMenu()
    {
        return $this->parent()->with('parentRoleMenu')->with('menu');
    }

    public function childRoleMenu()
    {
        return $this->child()->with('childRoleMenu')->with('menu');
    }

    public function group()
    {
        return $this->hasOne('App\Models\HRMS\Auth\RoleMaster','role_id','role_id');
    }

    public function user()
    {
        return $this->hasMany('App\Models\HRMS\Auth\UserMaster','role_id','role_id');
    }
}
