<?php

namespace App\Models\STXI\CEISA40;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DOCUMENTCEISA extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_ceisa40';
    protected $table = '03_DOKUMEN';
    protected $primaryKey = ['NOMOR AJU', 'SERI'];
    public $incrementing = false;
    protected $fillable = [
        'NOMOR AJU',
        'SERI',
        'KODE DOKUMEN',
        'NOMOR DOKUMEN',
        'TANGGAL DOKUMEN',
        'KODE FASILITAS',
        'KODE IJIN'
    ];
}