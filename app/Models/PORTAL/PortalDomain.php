<?php

namespace App\Models\PORTAL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalDomain extends Model
{
    use HasFactory;
    protected $table = 'portal_domain';
    protected $fillable = [
        'p_u_username',
        'pd_name',
        'pd_desc',
        'pd_prefix_db',
        'pd_img',
        'pd_base_color',
    ];
}
