<?php

namespace App\Models\STXI\LOG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class INSWDataJlsDetail extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_log';
    protected $table = 'Z_INTR_JLS_DET';
    protected $fillable = [
        'ZID_HSCODE',
        'ZIJD_TYPE',
        'ZIJD_DET_ID',
        'ZIJD_DET_EN',
    ];
}
