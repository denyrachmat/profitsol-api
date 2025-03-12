<?php

namespace App\Models;

use App\Models\PORTAL\PortalEduDet;
use App\Models\PORTAL\PortalFamDet;
use App\Models\PORTAL\PortalRoleUserMap;
use App\Models\PORTAL\PortalUserDet;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'email_verified_at',
        'password',
        'is_mobileacc'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function edu()
    {
        return $this->hasMany(PortalEduDet::class, 'username', 'u_username');
    }

    public function fam()
    {
        return $this->hasMany(PortalFamDet::class, 'username', 'u_username');
    }

    public function det()
    {
        return $this->hasOne(PortalUserDet::class, 'u_username', 'username');
    }

    public function roles()
    {
        return $this->hasMany(PortalRoleUserMap::class, 'u_username', 'username');
    }

    public function notif()
    {
        return $this->hasMany(PortalNotif::class, 'pnm_to_users', 'username');
    }

    public static function boot() {
        parent::boot();

        static::deleting(function($user) { // before delete() method call this
             $user->det()->delete();
             $user->fam()->delete();
             $user->edu()->delete();
             // do the rest of the cleanup...
        });
    }
}
