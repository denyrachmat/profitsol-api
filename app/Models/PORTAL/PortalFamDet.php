<?php

namespace App\Models\PORTAL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalFamDet extends Model
{
    use HasFactory;
    protected $table = 'portal_users_fam_det';

    protected $fillable = [
        'u_username',
        'pufd_first_name',
        'pufd_last_name',
        'pufd_relation',
        'pufd_phone',
        'pufd_birthday'
    ];
}
