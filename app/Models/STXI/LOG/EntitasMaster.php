<?php

namespace App\Models\STXI\LOG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntitasMaster extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_log';
    protected $table = 'MENTITAS';
    protected $fillable = [
        'NPWP',
        'NAMA',
        'ALMT',
        'KODEKTR',
        'NIB'
    ];
}
