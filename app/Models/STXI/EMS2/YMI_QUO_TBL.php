<?php

namespace App\Models\STXI\EMS2;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YMI_QUO_TBL extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_ems2';
    protected $table = 'YMI_QUO_MSTR_TBL';
    protected $fillable = [
        'YQMT_QUO_NO',
        'YQMT_ITMCD',
        'YQMT_BP',
        'YQMT_SP',
        'YQMT_RATE',
        'YQMT_SP_RPH',
        'YQMT_BGNDT',
        'YQMT_ENDDT',
        'YMQT_REMARK',
        'YMQT_REMARK2',
        'YQMT_EFFDT_RMK',
        'YQMT_MDL_RMK'
    ];
}
