<?php

namespace App\Models\DMS\Core;

use Illuminate\Database\Eloquent\Model;

class LogicMaster extends Model
{
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_logic_mstr';
    protected $fillable = [
        'logic_id',
        'comp_group_id',
        'apprv_id',
        'comp_name',
        'logic_cond',
        'logic_value',
        'logic_apprv_flag',
        'logic_notif_flag',
        'logic_only_flag'
    ];
}
