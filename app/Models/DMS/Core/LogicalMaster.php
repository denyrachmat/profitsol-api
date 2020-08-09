<?php

namespace App\Models\DMS\Core;

use Illuminate\Database\Eloquent\Model;

class LogicalMaster extends Model
{
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_logical_mstr';
    protected $fillable = [
        'logical_id',
        'apprv_group_id',
        'apprv_id',
        'logical_connection',
        'logical_type',
        'logical_cond',
        'logical_param',
        'logical_val_opt',
        'logical_val',
        'logical_parent'
    ];

    public function child()
    {
        return $this->hasOne('App\Models\DMS\Core\LogicalMaster', 'logical_parent', 'logical_id');
    }

    public function allChildList()
    {
        return $this->child()->with('allChildList');
    }
}
