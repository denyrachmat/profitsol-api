<?php

namespace App\Models\STXI\EMS2;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YPOMaster extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_ems2';
    protected $table = 'YPO_MSTR_TBL';
    protected $fillable = [
        'YPO_ITMCD',
        'YPO_REMARKS',
        'YPO_MRPDT',
        'YPO_MAILDT',
        'YPO_RCVDT',
        'YPO_PONO',
        'YPO_PODUEDT',
        'YPO_POQTY',
        'YPO_TXID',
        'YPO_STXI_PO',
        'YPO_TYPE',
    ];
}
