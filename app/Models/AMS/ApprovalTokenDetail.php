<?php

namespace App\Models\AMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Awobaz\Compoships\Compoships;

class ApprovalTokenDetail extends Model
{
    use HasFactory, SoftDeletes;
    protected $connection = 'sqlsrv_ams';
    protected $table = 'ams_apprv_token_det';

    protected $fillable = [
        'p_u_username',
        'amsm_id',
        'amstd_token',
        'amstd_emailto',
    ];

    public function hist() {
        return $this->hasMany(ApprovalHistDetail::class, 'amstd_token', 'amstd_token');
    }

    public function selectedHist() {
        return $this->hasMany(ApprovalHistDetail::class, 'amstd_token', 'amstd_token');
    }

    public function firstHist() {
        return $this->hasOne(ApprovalHistDetail::class, 'amsm_id', 'amsm_id')->orderBy('id');
    }

}
