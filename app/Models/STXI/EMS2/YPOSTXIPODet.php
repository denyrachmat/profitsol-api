<?php

namespace App\Models\STXI\EMS2;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YPOSTXIPODet extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_ems2';
    protected $table = 'YPO_STXI_PO_DET_TBL';
    protected $fillable = [
        'YMT_ID',
        'YSPDT_PONO',
        'YSPDT_INVNO',
        'YSPDT_POQT',
    ];
}
