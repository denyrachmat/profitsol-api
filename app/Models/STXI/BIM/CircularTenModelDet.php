<?php

namespace App\Models\STXI\BIM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CircularTenModelDet extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_bim';
    protected $table = 'CIRTEN_ITM_DET';
    protected $fillable = [
        'CM_ID',
        'CIM_ITMCD',
    ];
}
