<?php

namespace App\Models\DMS\Core;

use Illuminate\Database\Eloquent\Model;

class LogicMaster extends Model
{
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_logic_mstr';
}
