<?php

namespace App\Imports\STXI\LOG\Ceisa40;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

use App\Models\STXI\LOG\ITINVIncoming;
use App\Models\STXI\LOG\ITINVOutgoing;
use App\Models\STXI\LOG\ITINVUploadTemp;

class ImportEntitas implements ToModel, WithHeadingRow, SkipsEmptyRows
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
        logger('entitas start');
        ini_set("memory_limit", "3G");
        if(!array_filter($row)) {
            return null;
        }

        if (!empty(trim($row['nomor_aju']))) {
            $cekTempDataList = ITINVUploadTemp::where('NO_AJU', $row['nomor_aju'])->get()->toArray();

            foreach ($cekTempDataList as $key => $cekTempData) {

                if ($this->incout == 'INC') {
                    // Pengirim / Pengusaha
                    if ($row['kode_entitas'] == 9 || $row['kode_entitas'] == 3) {
                        ITINVUploadTemp::updateOrCreate([
                            'NO_AJU' => $row['nomor_aju'],
                            'NO_DAFTAR' => $cekTempData['NO_DAFTAR']
                        ], [
                            'NO_AJU' => $row['nomor_aju'],
                            'NO_DAFTAR' => $cekTempData['NO_DAFTAR'],
                            'PENGIRIM' => $row['nama_entitas']
                        ]);
                    } elseif ($row['kode_entitas'] == 7) {
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
                    if (($row['kode_entitas'] == 7) && !empty($cekTempData['NO_DAFTAR'])) {
                        ITINVUploadTemp::updateOrCreate([
                            'NO_AJU' => $row['nomor_aju'],
                            'NO_DAFTAR' => $cekTempData['NO_DAFTAR']
                        ], [
                            'NO_AJU' => $row['nomor_aju'],
                            'NO_DAFTAR' => $cekTempData['NO_DAFTAR'],
                            'PENGIRIM' => $row['nama_entitas']
                        ]);
                    }

                    if (($row['kode_entitas'] == 8) && !empty($cekTempData['NO_DAFTAR'])) {
                        ITINVUploadTemp::updateOrCreate([
                            'NO_AJU' => $row['nomor_aju'],
                            'NO_DAFTAR' => $cekTempData['NO_DAFTAR']
                        ], [
                            'NO_AJU' => $row['nomor_aju'],
                            'NO_DAFTAR' => $cekTempData['NO_DAFTAR'],
                            'PENERIMA' => $row['nama_entitas']
                        ]);
                    }
                }
            }
        }

        logger(json_encode($row));
    }
}
