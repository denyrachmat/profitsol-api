<?php

namespace App\Models\STXI\EMS2;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DLVTYODlvDet extends Model
{
	use SoftDeletes;
    use HasFactory;

    protected $connection = 'sqlsrv_ems2';
    protected $table = 'DLV_REQ_TYO_DET';
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'DRT_ITMCD',
        'DRT_PSI_DELDT',
        'DRT_TRANID',
        'DRT_DELDT',
    ];
}
