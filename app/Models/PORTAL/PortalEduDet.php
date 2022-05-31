<?php

namespace App\Models\PORTAL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalEduDet extends Model
{
    use HasFactory;
    protected $table = 'portal_users_study_det';

    protected $fillable = [
        'u_username',
        'pufd_id',
        'pusd_level',
        'pusd_sch_name',
        'pusd_sch_majors',
        'pusd_sch_minors',
        'pusd_sch_addr',
        'pusd_sch_start',
        'pusd_sch_end',
        'pusd_grade',
        'pusd_sch_passed'
    ];
}
