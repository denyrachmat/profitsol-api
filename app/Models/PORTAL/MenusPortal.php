<?php

namespace App\Models\PORTAL;

use Illuminate\Database\Eloquent\Model;

class MenusPortal extends Model
{
    protected $table = 'psi_portal_menu';

    public function parent()
    {
        return $this->belongsTo('App\Models\PORTAL\MenusPortal','id','menu_parent');
    }

    public function child()
    {
        return $this->hasMany('App\Models\PORTAL\MenusPortal','menu_parent','id');
    }

    public function role()
    {
        return $this->belongsTo('App\Models\PORTAL\RolesPortal','id','menu_id');
    }
}
