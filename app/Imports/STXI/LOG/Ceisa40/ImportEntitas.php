<?php

namespace App\Imports\STXI\LOG\Ceisa40;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

use App\Models\STXI\LOG\ITINVIncoming;
use App\Models\STXI\LOG\ITINVOutgoing;
use App\Models\STXI\LOG\ITINVUploadTemp;

class ImportEntitas implements ToModel, WithHeadingRow
{
    private $incout;

    public function __construct($incout)
    {
        $this->incout = $incout;
    }

    /**
    * @param Collection $collection
    */
    public function model(array $row)
    {
        ini_set("memory_limit", "3G");
        $cekTempData = ITINVUploadTemp::where('NO_AJU', $row['nomor_aju'])->first();
        
        if ($this->incout == 'INC') {
            if ($row['kode_entitas'] == 9) {
                ITINVUploadTemp::updateOrCreate([
                    'NO_AJU' => $row['nomor_aju'],
                    'NO_DAFTAR' => $cekTempData['NO_DAFTAR']
                ], [
                    'NO_AJU' => $row['nomor_aju'],
                    'NO_DAFTAR' => $cekTempData['NO_DAFTAR'],
                    'PENGIRIM' => $row['nama_entitas']
                ]);
            } elseif($row['kode_entitas'] == 7) {
                ITINVUploadTemp::updateOrCreate([
                    'NO_AJU' => $row['nomor_aju'],
                    'NO_DAFTAR' => $cekTempData['NO_DAFTAR']
                ], [
                    'NO_AJU' => $row['nomor_aju'],
                    'NO_DAFTAR' => $cekTempData['NO_DAFTAR'],
                    'SUPPL' => $row['nama_entitas']
                ]);
            }
        } else {
            if (($row['kode_entitas'] == 8 || $row['kode_entitas'] == 7) && !empty($cekTempData['NO_DAFTAR'])) {
                ITINVUploadTemp::updateOrCreate([
                    'NO_AJU' => $row['nomor_aju'],
                    'NO_DAFTAR' => $cekTempData['NO_DAFTAR']
                ], [
                    'NO_AJU' => $row['nomor_aju'],
                    'NO_DAFTAR' => $cekTempData['NO_DAFTAR'],
                    'PENGIRIM' => 'SUMITRONICS INDONESIA',
                    'PENERIMA' => $row['nama_entitas']
                ]);
            }
        }
    }
}
