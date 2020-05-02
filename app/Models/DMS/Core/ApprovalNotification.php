<?php

namespace App\Models\DMS\Core;

use Illuminate\Database\Eloquent\Model;

class ApprovalNotification extends Model
{
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_approval_notification';
    protected $fillable = [
        'apprv_user_from',
        'apprv_user_to',
        'apprv_hist_from_id',
        'apprv_hist_to_id',
        'apprv_read_flag',
    ];

    public function histFrom()
    {
        return $this->hasOne('App\Models\DMS\Core\ApprovalHist','id','apprv_hist_from_id');
    }

    public function histTo()
    {
        return $this->hasOne('App\Models\DMS\Core\ApprovalHist','id','apprv_hist_to_id');
    }

    public function userFrom()
    {
        return $this->hasOne('App\Models\DMS\Auth\UsersMaster','username','apprv_user_from');
    }

    public function userTo()
    {
        return $this->hasOne('App\Models\DMS\Auth\UsersMaster','username','apprv_user_to');
    }
}
