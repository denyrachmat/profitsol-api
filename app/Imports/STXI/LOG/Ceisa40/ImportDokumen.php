<?php

namespace App\Imports\STXI\LOG\Ceisa40;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use DB;

use App\Models\STXI\LOG\ITINVIncoming;
use App\Models\STXI\LOG\ITINVOutgoing;
use App\Models\STXI\LOG\ITINVUploadTemp;

class ImportDokumen implements ToModel, WithHeadingRow
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

        $jumlahInv = $jumlahDoc = 0;
        if ($this->incout == 'INC') {
            $baseDoc = ITINVIncoming::where('BCDOCNO', $cekTempData['NO_DAFTAR'])
                ->where('BCTYPE', $cekTempData['TYPE_BC'])
                ->where('BCDOCDT', $cekTempData['TGL_DAFTAR']);

            $jumlahInv = (clone $baseDoc)->where('HHEINVNO', $row['nomor_dokumen'])->count();
            $jumlahDoc = (clone $baseDoc)->where('DOCNO', $row['nomor_dokumen'])->count();
        } else {
            $baseDoc = ITINVOutgoing::where('BCDOCNO', $cekTempData['NO_DAFTAR'])
                ->where('BCTYPE', $cekTempData['TYPE_BC'])
                ->where('BCDOCDT', $cekTempData['TGL_DAFTAR']);

            $jumlahInv = (clone $baseDoc)->where('INVNO', $row['nomor_dokumen'])->count();
            $jumlahDoc = (clone $baseDoc)->where('DOCNO', $row['nomor_dokumen'])->count();
        }

        // Invoice
        if ($row['kode_dokumen'] == 380 && $jumlahInv === 0) {
            if ($this->incout == 'INC') {
                $cekIncoming = (clone $baseDoc)->whereNull(DB::raw('rtrim(HHEINVNO)'))
                    ->orWhere('HHEINVNO', '')
                    ->first();

                if (!empty($cekIncoming)) {
                    (clone $baseDoc)
                        ->update([
                            'HHEINVNO' => $row['nomor_dokumen']
                        ]);
                }
            } else {
                $cekOutgoing = (clone $baseDoc)->whereNull('INVNO')
                    ->first();

                if (!empty($cekOutgoing)) {
                    (clone $baseDoc)
                        ->update([
                            'INVNO' => $row['nomor_dokumen']
                        ]);
                }
            }
        }

        // sj
        if ($row['kode_dokumen'] == 640 && $jumlahDoc === 0) {
            if ($this->incout == 'INC') {
                $cekIncoming = (clone $baseDoc)->whereNull('DOCNO')
                    ->first();

                if (!empty($cekIncoming)) {
                    (clone $baseDoc)->whereNull('DOCNO')
                        ->update([
                            'DOCNO' => $row['nomor_dokumen']
                        ]);
                }
            } else {
                $cekOutgoing =(clone $baseDoc)->whereNull('DOCNO')
                    ->first();

                if (!empty($cekOutgoing)) {
                    (clone $baseDoc)
                        ->update([
                            'DOCNO' => $row['nomor_dokumen']
                        ]);
                }
            }
        }

        if ($row['kode_dokumen'] == 630 && $jumlahDoc === 0) {
            if ($this->incout == 'INC') {
                $cekIncoming = (clone $baseDoc)
                    ->whereNull('DOCNO')
                    ->first();

                if (!empty($cekIncoming)) {
                    (clone $baseDoc)
                        ->update([
                            'DOCNO' => $row['nomor_dokumen']
                        ]);
                }
            } else {
                $cekOutgoing = (clone $baseDoc)
                    ->whereNull('DOCNO')
                    ->first();

                if (!empty($cekOutgoing)) {
                    (clone $baseDoc)
                        ->update([
                            'DOCNO' => $row['nomor_dokumen']
                        ]);
                }
            }
        }

    }
}
