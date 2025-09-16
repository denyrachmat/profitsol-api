<?php

namespace App\Models\PORTAL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalRoleAppMap extends Model
{
    use \Awobaz\Compoships\Compoships;
    use HasFactory;

    protected $table = 'portal_role_app_map';
    protected $fillable = [
        'u_username',
        'rm_role_id',
        'am_app_id',
        'am_app_parent',
    ];

    public function apps()
    {
        return $this->hasOne('App\Models\PORTAL\PortalApp', 'am_app_code', 'am_app_id');
    }

    public function role()
    {
        return $this->hasOne('App\Models\PORTAL\PortalRole', 'id', 'rm_role_id');
    }

    public function child()
    {
        return $this->hasMany('App\Models\PORTAL\PortalRoleAppMap', ['am_app_parent', 'rm_role_id', 'u_username'], ['am_app_id', 'rm_role_id', 'u_username']);
    }

    public function childRoles($depth = 8)
    // {
    //     if ($depth <= 0)
    //         return $this->child();

    //     return $this->child()
    //         ->with([
    //             'childRoles' => function ($query) use ($depth) {
    //                 $query->with('apps')->take($depth - 1);
    //             }
    //         ])
    //         ->whereNotNull('am_app_id')
    //         ->with('apps')
    //         ->orderBy('am_app_id');
    // }
    {
        return $this->child()->with('childRoles')->with('apps')->orderBy('am_app_id');
    }
}
