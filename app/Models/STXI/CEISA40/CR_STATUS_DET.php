<?php

namespace App\Models\STXI\CEISA40;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CR_STATUS_DET extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_ceisa40';
    protected $table = 'CR_STATUS_DET';
    protected $fillable = [
        'ID_HEADER',
        'CRSD_NOMOR_AJU',
        'CRSD_RESNM',
        'CRSD_RESDTFR',
        'CRSD_RESDTTO',
    ];
}
