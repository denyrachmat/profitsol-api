<?php

namespace App\Models\STXI\EMS2;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DLVTYOHist extends Model
{
	use SoftDeletes;
    use HasFactory;

    protected $connection = 'sqlsrv_ems2';
    protected $table = 'DLV_REQ_SMT_TYO';
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'MITM_MODELCD',
        'IO_QTY',
        'IO_REMARK',
        'IPP_REMARK',
        'RANK_REMARK',
        'DEL_DATE',
        'DRST_JOBNO'
    ];
}
