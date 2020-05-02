<?php

namespace App\Models\DMS\Auth;

use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;

class UsersMaster extends Model
{
    use HasApiTokens;

    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_user_mstr';

    protected $fillable = [
        'username',
        'email',
        'token',
        'password_hash',
        'password_sha',
        'role_id',
        'status',
        'first_name',
        'last_name',
    ];

    public function role()
    {
        return $this->hasMany('App\Models\DMS\Auth\RolesMaster','role_identifier','role_id');
    }

    public function menu()
    {
        return $this->hasManyThrough(
            'App\Models\DMS\Auth\MenusMaster',
            'App\Models\DMS\Auth\RolesMaster',
            'role_identifier',
            'id',
            'role_id',
            'menu_id'
        );
    }

    public function approval()
    {
        return $this->hasMany('App\Models\DMS\Core\ApprovalMaster','apprv_author','username');
    }

    public function doc()
    {
        return $this->hasMany('App\Models\DMS\Core\DocsMaster','doc_author','username');
    }

    public function docLocation()
    {
        return $this->hasMany('App\Models\DMS\Core\DocsLocationMaster','creator_loc','username')->where('parent_loc','0');
    }

    public function approver()
    {
        return $this->hasMany('App\Models\DMS\Core\ApprovalMaster','apprv_author','username');
    }
}
