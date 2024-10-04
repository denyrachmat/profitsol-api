<?php

namespace App\Models\AMS;

use App\Models\PORTAL\PortalUserDet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Awobaz\Compoships\Compoships;

class ApprovalHistDetail extends Model
{
    use HasFactory, SoftDeletes, Compoships;
    protected $connection = 'sqlsrv_ams';
    protected $table = 'ams_apprv_hist_det';

    protected $fillable = [
        'p_u_username',
        'amsm_id',
        'amsmd_id',
        'amshd_token',
        'amstd_token',
        'amshd_username',
        'amshd_username_apprv',
        'amshd_stat',
        'amshd_remarks',
        'amshd_paramstore',
        'readed_at'
    ];

    public function senderUser() {
        return $this->setConnection('sqlsrv')->hasOne(PortalUserDet::class, 'u_username', 'p_u_username');
    }

    public function receiveUser() {
        return $this->setConnection('sqlsrv')->hasOne(PortalUserDet::class, 'u_username', 'amshd_username_apprv');
    }

    public function mapdet() {
        return $this->hasOne(ApprovalMapDetail::class, 'id', 'amsmd_id');
    }
}
