<?php

namespace App\Models\AMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalHistDetail extends Model
{
    use HasFactory, SoftDeletes;
    protected $connection = 'sqlsrv_ams';
    protected $table = 'ams_apprv_hist_det';

    protected $fillable = [
        'p_u_username',
        'amsm_id',
        'amsmd_id',
        'amshd_token',
        'amshd_username',
        'amshd_username_apprv',
        'amshd_stat',
        'amshd_remarks',
    ];
}
