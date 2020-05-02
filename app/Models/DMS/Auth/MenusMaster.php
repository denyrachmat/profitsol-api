<?php

namespace App\Models\DMS\Auth;

use Illuminate\Database\Eloquent\Model;

class MenusMaster extends Model
{
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_menu_mstr';

    public function parent()
    {
        return $this->belongsTo('App\Models\DMS\Auth\MenusMaster','id','menu_parent');
    }

    public function child()
    {
        return $this->hasMany('App\Models\DMS\Auth\MenusMaster','menu_parent','id');
    }

    public function role()
    {
        return $this->belongsTo('App\Models\DMS\Auth\RolesMaster','id','menu_id');
    }
}
