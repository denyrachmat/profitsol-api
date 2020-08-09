<?php

namespace App\Models\HRMS\Core\Bio;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\CompositeKey;

class UserEduDet extends Model
{
    use CompositeKey;

    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_user_education_det';

    protected $primaryKey = [
        'username',
        'user_edu_level'
    ];

    protected $fillable = [
        'username',
        'user_edu_level',
        'user_edu_level_desc',
        'user_edu_sc_name',
        'user_edu_major',
        'user_edu_city',
        'user_edu_month_from',
        'user_edu_year_from',
        'user_edu_month_to',
        'user_edu_year_to'
    ];
}
