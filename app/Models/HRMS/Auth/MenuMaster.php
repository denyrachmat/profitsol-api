<?php

namespace App\Models\HRMS\Auth;

use Illuminate\Database\Eloquent\Model;

class MenuMaster extends Model
{
    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_menu_mstr';

    public function parent()
    {
        return $this->hasOne('App\Models\HRMS\Auth\MenuMaster','id','menu_parent_id');
    }

    public function child()
    {
        return $this->hasMany('App\Models\HRMS\Auth\MenuMaster','menu_parent_id','id');
    }

    public function childMenu()
    {
        return $this->child()->with('childMenu');
    }
}
