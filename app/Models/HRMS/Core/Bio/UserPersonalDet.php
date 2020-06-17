<?php

namespace App\Models\HRMS\Core\Bio;

use Illuminate\Database\Eloquent\Model;

class UserPersonalDet extends Model
{
    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_user_personal_det';
    protected $fillable = [
        'username',
        'user_nat',
        'user_province1',
        'user_province2',
        'user_city1',
        'user_city2',
        'user_district1',
        'user_district2',
        'user_subdistrict1',
        'user_subdistrict2',
        'user_zip1',
        'user_zip2',
        'user_detaddr1',
        'user_detaddr2',
        'user_phone',
        'user_handphone',
        'user_religion',
        'user_bloodtype',
        'user_height',
        'user_weight',
        'user_gender',
        'user_marital_status',
        'user_marital_wive',
        'user_marital_child',
        'user_birthplace',
        'user_birthdate',
    ];
}
