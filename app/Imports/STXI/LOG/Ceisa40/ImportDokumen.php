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

        $cekKosong = array_filter($row, function ($f) {
            if (!empty($f)) {
                return $f;
            }
        });

        if (count($cekKosong) > 0) {

            $cekTempData = ITINVUploadTemp::where('NO_AJU', $row['nomor_aju'])->first();

            $jumlahInv = $jumlahDoc = 0;
            if ($this->incout == 'INC') {
                $baseDoc = ITINVIncoming::where(DB::raw('LEFT(BCDOCNO, 6)'), substr($cekTempData['NO_DAFTAR'], 0, 6))
                    ->where('BCTYPE', $cekTempData['TYPE_BC'])
                    ->where('BCDOCDT', $cekTempData['TGL_DAFTAR']);

                $jumlahInv = (clone $baseDoc)->where('HHEINVNO', $row['nomor_dokumen'])->count();
                $jumlahDoc = (clone $baseDoc)->where('DOCNO', $row['nomor_dokumen'])->count();
            } else {
                $baseDoc = ITINVOutgoing::where(DB::raw('LEFT(BCDOCNO, 6)'), substr($cekTempData['NO_DAFTAR'], 0, 6))
                    ->where('BCTYPE', $cekTempData['TYPE_BC'])
                    ->where('BCDOCDT', $cekTempData['TGL_DAFTAR']);

                $jumlahInv = (clone $baseDoc)->where('INVNO', $row['nomor_dokumen'])->count();
                $jumlahDoc = (clone $baseDoc)->where('DOCNO', $row['nomor_dokumen'])->count();
            }

            // Invoice
            if ($row['kode_dokumen'] == 380 && $jumlahInv === 0) {
                logger('Inv ' . $row['nomor_dokumen']);
                logger('Jumlah Inv ' . $jumlahInv);
                if ($this->incout == 'INC') {
                    $cekIncoming = (clone $baseDoc)
                        ->whereNull(DB::raw('rtrim(HHEINVNO)'))
                        ->orWhere('HHEINVNO', '')
                        ->get();

                    logger(json_encode($cekIncoming));
                    if (count($cekIncoming) > 0) {
                        foreach ($cekIncoming->pluck('ITMCD') as $key => $valueItm) {
                            (clone $baseDoc)
                                ->where('ITMCD', $valueItm)
                                ->update([
                                    'HHEINVNO' => $row['nomor_dokumen']
                                ]);
                        }
                    }
                } else {
                    $cekOutgoing = (clone $baseDoc)
                        ->whereNull('INVNO')
                        ->orWhere('INVNO', '')
                        ->get();

                    if (count($cekOutgoing) > 0) {
                        foreach ($cekOutgoing->pluck('ITMCD') as $key => $valueItm) {
                            (clone $baseDoc)
                                ->where('ITMCD', $valueItm)
                                ->update([
                                    'INVNO' => $row['nomor_dokumen']
                                ]);
                        }
                    }
                }
            }

            // sj
            if (($row['kode_dokumen'] == 640 || $row['kode_dokumen'] == 630) && $jumlahDoc === 0) {                
                logger('Inv ' . $row['nomor_dokumen']);
                logger('Jumlah Doc ' . $jumlahDoc);
                if ($this->incout == 'INC') {
                    $cekIncoming = (clone $baseDoc)
                        ->whereNull('DOCNO')
                        ->orWhere('DOCNO', '')
                        ->get();

                    if (count($cekIncoming) > 0) {
                        (clone $baseDoc)
                            ->whereIn('ITMCD', $cekIncoming->pluck('ITMCD'))
                            ->update([
                                'DOCNO' => $row['nomor_dokumen']
                            ]);
                    }
                } else {
                    $cekOutgoing = (clone $baseDoc)
                        ->whereNull('DOCNO')
                        ->orWhere('DOCNO', '')
                        ->get();

                    if (count($cekOutgoing) > 0) {
                        (clone $baseDoc)
                            ->whereIn('ITMCD', $cekOutgoing->pluck('ITMCD'))
                            ->update([
                                'DOCNO' => $row['nomor_dokumen']
                            ]);
                    }
                }
            }
        }
    }
}
