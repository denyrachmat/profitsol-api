<?php

namespace App\Models\HRMS\Core\Bio;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\CompositeKey;

class UserEmergDet extends Model
{
    use CompositeKey;

    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_user_emergency_person_det';

    protected $primaryKey = [
        'username',
        'emerg_first_name',
        'emerg_last_name'
    ];

    protected $fillable = [
        'username',
        'emerg_first_name',
        'emerg_last_name',
        'emerg_email',
        'emerg_phone',
        'emerg_handphone',
        'emerg_address',
        'emerg_relation'
    ];
}
