<?php

namespace App\Models\HRMS\Core\Bio;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\CompositeKey;

class UserExpDet extends Model
{
    use CompositeKey;
    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_user_work_exp_det';
    protected $primaryKey = [
        'username',
        'company_name'
    ];

    protected $fillable = [
        'username',
        'company_name',
        'company_address',
        'company_start_work',
        'company_end_work',
        'company_resign_reason'
    ];
}
