<?php

namespace App\Models\STXI\EMS2;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DLVTYOWkRpt extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_ems2';
    protected $table = 'DLV_REQ_WK_UPL_RPT';

    protected $fillable = [
        'ITEM_CODE',
        'PO_NUM',
        'ORDER_DATE',
        'DUE_DATE',
        'ORDER_QTY',
        'RCV_QTY',
        'PIC',
        'UPLOAD_DATE'
    ];
}
