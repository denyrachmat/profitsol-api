<?php

namespace App\Models\DMS\Core;

use Illuminate\Database\Eloquent\Model;

class CompMaster extends Model
{
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_comp_mstr';
}
