<?php

namespace App\Models\PORTAL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalApp extends Model
{
    use HasFactory;

    protected $table = 'portal_app_mstr';
    protected $fillable = [
        'u_username',
        'am_app_code',
        'am_app_name',
        'am_app_icon',
        'am_app_desc',
        'am_app_url',
        'am_app_parent',
        'am_is_files'
    ];

    public function child()
    {
        return $this->hasMany('App\Models\PORTAL\PortalApp','am_app_parent','am_app_code');
    }

    public function childApps()
    {
        return $this->child()->with('childApps');
    }
}
