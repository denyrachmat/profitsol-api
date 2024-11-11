<?php

namespace App\Models\AMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalSetDetail extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_ams';
    protected $table = 'ams_apprv_set_det';

    protected $fillable = [
        'p_u_username',
        'amsm_id',
        'amssd_quotkn',
        'amssd_isemail',
        'amssd_iswa',
        'amssd_issms',
        'amssd_is_docsign',
        'amssd_unread_autonotif',
        'amssd_unread_chktime',
        'amssd_autorun',
        'amssd_autorun_chktime',
        'amssd_content',
        'amssd_attachment'
    ];

    public function attch () {
        return $this->hasMany(ApprovalAttachSet::class, 'aasd_id', 'id');
    }
}
