<?php

namespace App\Models\STXI\CEISA40;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ENTITASCEISA extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_ceisa40';
    protected $table = '02_ENTITAS';
    protected $primaryKey = ['NOMOR AJU', 'SERI'];
    public $incrementing = false;
    protected $fillable = [
        'NOMOR AJU',
        'SERI',
        'KODE ENTITAS',
        'KODE JENIS IDENTITAS',
        'NOMOR IDENTITAS',
        'NAMA ENTITAS',
        'ALAMAT ENTITAS',
        'NIB ENTITAS',
        'KODE JENIS API',
        'KODE STATUS',
        'NOMOR IJIN ENTITAS',
        'TANGGAL IJIN ENTITAS',
        'KODE NEGARA',
        'NIPER ENTITAS',
        'KODE KATEGORI KONSOLIDATOR',
        'KODE AFILIASI'
    ];
}