<?php

namespace App\Models\PORTAL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalApp extends Model
{
    use HasFactory;

    protected $table = 'portal_app_mstr';
    protected $fillable = [
        'am_app_code',
        'am_app_name',
        'am_app_desc',
        'am_app_url'
    ];
}
