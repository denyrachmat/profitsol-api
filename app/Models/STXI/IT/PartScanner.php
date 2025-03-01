<?php

namespace App\Models\STXI\IT;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartScanner extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_it';
    protected $table = 'EMS_LBL_VALID';
    protected $fillable = [
        'MBCSCNH_ITMCD',
        'MBCSCNH_QTY',
        'MBCSCNH_LOT',
        'MBCSCNH_VALID',
        'MBCSCNH_REMARKS',
    ];
}
