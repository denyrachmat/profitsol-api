<?php

namespace App\Models\STXI\BIM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CircularTenMstr extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_bim';
    protected $table = 'CIRTEN_MSTR';
    protected $fillable = [
        'CIRTEN_NO',
        'CIRTEN_GENDT',
        'CIRTEN_MAILDT',
        'CIRTEN_DMS_DOC_ID'
    ];
}
