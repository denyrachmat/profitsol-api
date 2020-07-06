<?php

namespace App\Models\HRMS\Core\Bio;

use Illuminate\Database\Eloquent\Model;

class UserChildrenDet extends Model
{
    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_user_child_det';
    protected $fillable = [
        'username',
        'child_name',
        'child_birthdate',
        'child_birthplace',
        'child_gender',
        'child_last_edu'
    ];
}
