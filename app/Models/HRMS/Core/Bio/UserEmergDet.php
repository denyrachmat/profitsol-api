<?php

namespace App\Models\HRMS\Core\Bio;

use Illuminate\Database\Eloquent\Model;

class UserEmergDet extends Model
{
    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_user_emergency_person_det';
    protected $fillable = [
        'username',
        'emerg_first_name',
        'emerg_last_name',
        'emerg_email',
        'emerg_phone',
        'emerg_handphone',
        'emerg_province',
        'emerg_city',
        'emerg_urban',
        'emerg_suburban',
        'emerg_addr_det',
        'emerg_desc'
    ];
}
