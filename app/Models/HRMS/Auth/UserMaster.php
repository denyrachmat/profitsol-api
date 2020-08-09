<?php

namespace App\Models\HRMS\Auth;

use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserMaster extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_user_mstr';

    protected $fillable = [
        'username',
        'first_name',
        'last_name',
        'email',
        'token',
        'password_hash',
        'password_sha',
        'role_id',
        'division_id',
        'signature_id',
        'occ_id',
        'domain_id',
        'status',
        'verified_at',
        'api_token'
    ];

    public function roleMaster()
    {
        return $this->hasOne('App\Models\HRMS\Auth\RoleMaster','role_id','role_id');
    }

    public function personalDetail()
    {
        return $this->hasOne('App\Models\HRMS\Core\Bio\UserPersonalDet','username','username');
    }
    
    public function educationDetail()
    {
        return $this->hasMany('App\Models\HRMS\Core\Bio\UserEduDet','username','username');
    }

    public function childrenDetail()
    {
        return $this->hasMany('App\Models\HRMS\Core\Bio\UserChildrenDet','username','username');
    }

    public function expDetail()
    {
        return $this->hasMany('App\Models\HRMS\Core\Bio\UserExpDet','username','username');
    }

    public function occ()
    {
        return $this->hasOne('App\Models\HRMS\Core\Structure\occMaster','id','occ_id');
    }
}
