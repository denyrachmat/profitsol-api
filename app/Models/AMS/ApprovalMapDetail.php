<?php

namespace App\Models\AMS;

use App\Models\PORTAL\PortalUserDet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalMapDetail extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_ams';
    protected $table = 'ams_apprv_map_det';

    protected $fillable = [
        'p_u_username',
        'amsm_id',
        'amsmd_username',
        'amsmd_order',
        'amsmd_reqaprv',
    ];

    public function userDet() {
        return $this->setConnection('sqlsrv')->hasOne(PortalUserDet::class, 'u_username', 'amsmd_username');
    }

    public function hist() {
        return $this->hasMany(ApprovalHistDetail::class, 'amsmd_id', 'id');
    }
}
