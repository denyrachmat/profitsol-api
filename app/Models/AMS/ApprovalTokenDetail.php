<?php

namespace App\Models\AMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalTokenDetail extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_ams';
    protected $table = 'ams_apprv_token_det';

    protected $fillable = [
        'p_u_username',
        'amsm_id',
        'amstd_token',
        'amstd_emailto',
    ];
}
