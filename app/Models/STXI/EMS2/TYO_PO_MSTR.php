<?php

namespace App\Models\STXI\EMS2;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TYO_PO_MSTR extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_ems2';
    protected $table = 'TYO_PO_MSTR';
    protected $fillable = [
        'TPM_ITMCD',
        'TPM_ORDERNO',
        'TPM_DLVDT',
        'TPM_STATUS',
        'TPM_ORDERQTY',
        'TPM_CSVOUTDT',
        'TPM_ORDER_CRTDT',
        'TPM_ORDER_REGDT',
        'TPM_PRC',
        'TPM_RPLY_DEADLNDT',
        'TPM_STOREID',
        'TPM_ISSDT'
    ];
}
