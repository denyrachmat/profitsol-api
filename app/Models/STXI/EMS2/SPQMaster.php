<?php

namespace App\Models\STXI\EMS2;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SPQMaster extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_ems2';
    protected $table = 'SPQ_MSTR_TBL';
    protected $fillable = [
        'MITM_MODELCD',
        'MITM_PCBCD',
        'STXI_SPQ',
        'SPQ_BOX_PROT_FLAG'
    ];
}
