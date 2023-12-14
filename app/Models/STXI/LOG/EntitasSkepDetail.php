<?php

namespace App\Models\STXI\LOG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntitasSkepDetail extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_log';
    protected $table = 'MSKEP';
    protected $fillable = [
        'NPWP',
        'NOSKEP',
        'EFFDT',
    ];
}
