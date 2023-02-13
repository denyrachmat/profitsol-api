<?php

namespace App\Models\STXI\EMS2;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FRCST_DLV_TYO extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_ems2';
    protected $table = 'FRCST_DLV_TYO';
    protected $fillable = [
        'FDT_ITMCD',
        'FDT_MONTH',
        'FDT_YEAR',
        'FDT_QTY',
        'created_at',
        'updated_at'
    ];
}
