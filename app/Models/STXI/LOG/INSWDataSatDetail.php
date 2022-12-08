<?php

namespace App\Models\STXI\LOG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class INSWDataSatDetail extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_log';
    protected $table = 'Z_INTR_JLS_DET';
    protected $fillable = [
        'ZID_HSCODE',
        'ZISD_TYPE',
        'ZISD_SERI',
        'ZISD_JENIS',
        'ZISD_SATUAN',
    ];
}
