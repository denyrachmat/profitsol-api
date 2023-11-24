<?php

namespace App\Imports\STXI\LOG\Ceisa40;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

use App\Models\STXI\LOG\ITINVIncoming;
use App\Models\STXI\LOG\ITINVOutgoing;
use App\Models\STXI\LOG\ITINVUploadTemp;

class ImportDokumen implements ToModel, WithHeadingRow
{
    /**
     * @param Collection $collection
     */
    public function model(array $row)
    {
        ini_set("memory_limit", "3G");
        if ($row['kode_dokumen'] == 380) {
            $cekTempData = ITINVUploadTemp::where('NO_AJU', $row['nomor_aju'])->first();
            if ($cekTempData->STATE_FLG == 'INC') {
                $cekIncoming = ITINVIncoming::where('BCDOCNO', $cekTempData['NO_DAFTAR'])
                    ->where('BCTYPE', $cekTempData['TYPE_BC'])
                    ->where('BCDOCDT', $cekTempData['TGL_DAFTAR'])
                    ->where('ITMCD', trim($row['kode_barang']))
                    ->first();
    
                if (!empty($cekIncoming)) {
                    ITINVIncoming::where("BCDOCNO", $cekTempData['NO_DAFTAR'])
                        ->where('BCTYPE', $cekTempData['TYPE_BC'])
                        ->where('BCDOCDT', $cekTempData["TGL_DAFTAR"])
                        ->update([
                            'HHEINVNO' => $row['nomor_dokumen']
                        ]);
                }
            } else {
                $cekOutgoing = ITINVOutgoing::where('BCDOCNO', $cekTempData['NO_DAFTAR'])
                ->where('BCTYPE', $cekTempData['TYPE_BC'])
                ->where('BCDOCDT', $cekTempData['TGL_DAFTAR'])
                ->where('ITMCD', trim($row['kode_barang']))
                ->first();

                if (!empty($cekOutgoing)) {
                    ITINVOutgoing::where("BCDOCNO", $cekTempData['NO_DAFTAR'])
                        ->where('BCTYPE', $cekTempData['TYPE_BC'])
                        ->where('BCDOCDT', $cekTempData["TGL_DAFTAR"])
                        ->update([
                            'INVNO' => $row['nomor_dokumen']
                        ]);
                }
            }
        }
    }
}
