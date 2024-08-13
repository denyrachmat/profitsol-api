<?php

namespace App\Models\STXI\LOG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class INSWDataJlsDetail extends Model
{
    use HasFactory, SoftDeletes;
    protected $connection = 'sqlsrv_log';
    protected $table = 'Z_INTR_JLS_DET';
    protected $fillable = [
        'ZID_HSCODE',
        'ZIJD_TYPE',
        'ZIJD_DET_ID',
        'ZIJD_DET_EN',
    ];
}
