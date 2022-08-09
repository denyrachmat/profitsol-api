<?php

namespace App\Models\STXI\EMS2;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FRCST_PO_MRI extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_ems2';
    protected $table = 'FRCST_PO_MRI';
    protected $fillable = [
        'FPM_ITMCD',
        'FPM_UPLDT',
        'FPM_QTY',
    ];
}
