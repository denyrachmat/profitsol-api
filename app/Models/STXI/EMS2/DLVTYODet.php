<?php

namespace App\Models\STXI\EMS2;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DLVTYODet extends Model
{
	use SoftDeletes;
    use HasFactory;

    protected $connection = 'sqlsrv_ems2';
    protected $table = 'DLV_REQ_DET';
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'DRST_ID',
        'DRD_DELNO',
        'DRD_PRICE',
        'DRD_QTY',
        'DRD_DELDT',
    ];
}
