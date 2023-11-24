<?php

namespace App\Models\STXI\LOG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ITINVUploadTemp extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_itinv';
    protected $table = 'upload_ceisa40_tmp';

    protected $fillable = [
        'NO_AJU',
        'NO_DAFTAR',
        'TGL_DAFTAR',
        'TYPE_BC',
        'CURR',
        'PENGIRIM',
        'SUPPL',
        'PENERIMA',
        'STATE_FLG'
    ];
}
