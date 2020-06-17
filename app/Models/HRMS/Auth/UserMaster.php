<?php

namespace App\Models\HRMS\Auth;

use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;

class UserMaster extends Model
{
    use HasApiTokens;

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
    ];

    public function roleMaster()
    {
        return $this->hasOne('App\Models\HRMS\Auth\RoleMaster','role_id','role_id');
    }
}
