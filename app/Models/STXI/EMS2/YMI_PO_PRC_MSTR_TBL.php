<?php

namespace App\Models\STXI\EMS2;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YMI_PO_PRC_MSTR_TBL extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_ems2';
    protected $table = 'YMI_PO_PRC_MSTR_TBL';
    protected $fillable = [
        'YPPMT_PONO',
        'YPPMT_POLNO',
        'YPPMT_ITMCD',
        'YQMT_SP',
        'YQMT_ID',
    ];
}
