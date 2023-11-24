<?php

namespace App\Models\STXI\CEISA40;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CEISARESPON extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_ceisa40';
    protected $table = '00_CEISARESPON';
    protected $primaryKey = 'NOMOR_AJU';
    protected $fillable = [
        'NOMOR_AJU',
        'NOMOR_DAFTAR',
        'RES_DATE',
        'RES_TYPE',
        'RES_NO',
        'TYPE_DOC',
        'TGL_DAFTAR',
    ];
}
