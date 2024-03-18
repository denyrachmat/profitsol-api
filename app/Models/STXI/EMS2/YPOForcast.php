<?php

namespace App\Models\STXI\EMS2;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YPOForcast extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_ems2';
    protected $table = 'YPO_FC_DATA_DET';
    protected $fillable = [
        'YFDD_ITMCD',
        'YFDD_YEAR',
        'YFDD_MONTH',
        'YFDD_FCQT',
    ];
}
