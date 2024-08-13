<?php

namespace App\Models\STXI\LOG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class INSWDataRegDet extends Model
{
    use HasFactory, SoftDeletes;
    protected $connection = 'sqlsrv_log';
    protected $table = 'Z_INTR_REG_DET';
    protected $fillable = [
        'ZID_HSCODE',
        'ZIRD_TYPE',
        'ZIRD_NMIJIN',
        'ZIRD_KDIJIN',
        'ZIRD_DESC',
        'ZIRD_BEALIST',
        'ZIRD_LEGAL',
        'ZIRD_MODUL',
        'ZIRD_SKEPNO',
    ];
}
