<?php

namespace App\Models\HRMS\Core\Bio;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\CompositeKey;

class UserChildrenDet extends Model
{
    use CompositeKey;

    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_user_child_det';
    protected $primaryKey = [
        'username',
        'child_name'
    ];
    protected $fillable = [
        'username',
        'child_name',
        'child_birthdate',
        'child_birthplace',
        'child_gender',
        'child_last_edu'
    ];
}
