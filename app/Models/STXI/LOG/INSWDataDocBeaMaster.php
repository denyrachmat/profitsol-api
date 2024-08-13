<?php

namespace App\Models\STXI\LOG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class INSWDataDocBeaMaster extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_log';
    protected $table = 'Z_INTR_DOC_BEA_DET';

    protected $fillable = [
        'ZIDBD_DOCCD',
        'ZIDBD_DOCNM',
        'ZIDBD_DOCNMINTR',
        'ZIDBD_LINK',
        'ZIDBD_TLINK',
        'ZIDBD_DESC',
        'ZIDBD_DESCINTR',
    ];
}
