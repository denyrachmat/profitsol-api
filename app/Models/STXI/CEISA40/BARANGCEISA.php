<?php

namespace App\Models\STXI\CEISA40;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BARANGCEISA extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_ceisa40';
    protected $table = '07_BARANG';
    protected $primaryKey = ['NOMOR AJU', 'SERI'];
    public $incrementing = false;
    protected $fillable = [
        'NOMOR AJU',
        'SERI BARANG',
        'HS',
        'KODE BARANG',
        'URAIAN',
        'MEREK',
        'TIPE',
        'UKURAN',
        'SPESIFIKASI LAIN',
        'KODE SATUAN',
        'JUMLAH SATUAN',
        'KODE KEMASAN',
        'JUMLAH KEMASAN',
        'KODE DOKUMEN ASAL',
        'KODE KANTOR ASAL',
        'NOMOR DAFTAR ASAL',
        'TANGGAL DAFTAR ASAL',
        'NOMOR AJU ASAL',
        'SERI BARANG ASAL',
        'NETTO',
        'BRUTO',
        'VOLUME',
        'SALDO AWAL',
        'SALDO AKHIR',
        'JUMLAH REALISASI',
        'CIF',
        'CIF RUPIAH',
        'NDPBM',
        'FOB',
        'ASURANSI',
        'FREIGHT',
        'NILAI TAMBAH',
        'DISKON',
        'HARGA PENYERAHAN',
        'HARGA PEROLEHAN',
        'HARGA SATUAN',
        'HARGA EKSPOR',
        'HARGA PATOKAN',
        'NILAI BARANG',
        'NILAI JASA',
        'NILAI DANA SAWIT',
        'NILAI DEVISA',
        'PERSENTASE IMPOR',
        'KODE ASAL BARANG',
        'KODE DAERAH ASAL',
        'KODE GUNA BARANG',
        'KODE JENIS NILAI',
        'JATUH TEMPO ROYALTI',
        'KODE KATEGORI BARANG',
        'KODE KONDISI BARANG',
        'KODE NEGARA ASAL',
        'KODE PERHITUNGAN',
        'PERNYATAAN LARTAS',
        'FLAG 4 TAHUN',
        'SERI IZIN',
        'TAHUN PEMBUATAN',
        'KAPASITAS SILINDER',
        'KODE BKC',
        'KODE KOMODITI BKC',
        'KODE SUB KOMODITI BKC',
        'FLAG TIS',
        'ISI PER KEMASAN',
        'JUMLAH DILEKATKAN',
        'JUMLAH PITA CUKAI',
        'HJE CUKAI',
        'TARIF CUKAI',
        'KODE JENIS EKSPOR',
        'METODE PENENTUAN NILAI',
        'ALASAN METODE PENENTUAN NILAI',
        'STATEMENT PERBEDAAN HARGA'
    ];
}