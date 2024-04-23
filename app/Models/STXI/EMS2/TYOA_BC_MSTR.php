<?php

namespace App\Models\STXI\EMS2;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TYOA_BC_MSTR extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_ems2';
    protected $table = 'TYOA_BC_MSTR';
    protected $fillable = [
        'TYOA_ID',
        'TYOAM_PONO',
        'TYOAM_ITMCD',
        'TYOAM_QTY',
        'TYOAM_JOBNO',
        'TYOAM_DLVDT',
        'TYOAM_STAT',
        'TYOAM_REMARKS'
    ];
}
