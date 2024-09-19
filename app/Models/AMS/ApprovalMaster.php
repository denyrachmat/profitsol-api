<?php

namespace App\Models\AMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalMaster extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_ams';
    protected $table = 'ams_apprv_mstr';

    protected $fillable = [
        'p_u_username',
        'ams_idapv',
        'ams_title',
    ];

    public function det() {
        return $this->hasMany(ApprovalMapDetail::class, 'amsm_id', 'id');
    }
}
