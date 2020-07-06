<?php

namespace App\Models\HRMS\Core\Bio;

use Illuminate\Database\Eloquent\Model;

class UserExpDet extends Model
{
    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_user_work_exp_det';
    protected $fillable = [
        'username',
        'company_name',
        'company_address',
        'company_start_work',
        'company_end_work',
        'company_resign_reason'
    ];
}
