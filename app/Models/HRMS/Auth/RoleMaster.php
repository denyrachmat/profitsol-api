<?php

namespace App\Models\HRMS\Auth;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\CompositeKey;

class RoleMaster extends Model
{
    use CompositeKey;

    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_role_mstr';
    protected $primaryKey = [
        'role_id'
    ];

    protected $fillable = [
        'role_id',
        'role_name'
    ];

    public function mappingMenu()
    {
        return $this->hasMany('App\Models\HRMS\Auth\RoleMappingMenu','role_id','role_id');
    }
}
