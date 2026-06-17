<?php

namespace App\Imports\STXI\LOG\Ceisa40;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use DB;

use App\Models\STXI\LOG\ITINVIncoming;
use App\Models\STXI\LOG\ITINVOutgoing;
use App\Models\STXI\LOG\ITINVUploadTemp;

class ImportDokumen implements ToModel, WithHeadingRow, SkipsEmptyRows
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
        logger('dok start');
        ini_set("memory_limit", "3G");

        if (!array_filter($row)) {
            return null;
        }

        if (!empty(trim($row['nomor_aju']))) {
            $cekKosong = array_filter($row, function ($f) {
                if (!empty($f)) {
                    return $f;
                }
            });

            if (count($cekKosong) > 0) {
                $time_start = microtime(true);
                $cekTempData = ITINVUploadTemp::where('NO_AJU', $row['nomor_aju'])->first();
                $time_end = microtime(true);
                $execution_time = ($time_end - $time_start) / 60;

                logger('Dokumen fetch: Total time cek Ceisa Temp Data: ' . $execution_time);
                $jumlahInv = $jumlahDoc = 0;

                $time_start = microtime(true);
                if ($this->incout == 'INC') {
                    $baseDoc = ITINVIncoming::NoLock()->where(DB::raw('LEFT(BCDOCNO, 6)'), substr($cekTempData['NO_DAFTAR'], 0, 6))
                        ->where('BCTYPE', $cekTempData['TYPE_BC'])
                        ->where('BCDOCDT', $cekTempData['TGL_DAFTAR']);

                    $baseDocUpdate = ITINVIncoming::where(DB::raw('LEFT(BCDOCNO, 6)'), substr($cekTempData['NO_DAFTAR'], 0, 6))
                        ->where('BCTYPE', $cekTempData['TYPE_BC'])
                        ->where('BCDOCDT', $cekTempData['TGL_DAFTAR']);

                    $jumlahInv = (clone $baseDoc)->where('HHEINVNO', $row['nomor_dokumen'])->count();
                    $jumlahDoc = (clone $baseDoc)->where('DOCNO', $row['nomor_dokumen'])->count();
                } else {
                    $baseDoc = ITINVOutgoing::NoLock()->where(DB::raw('LEFT(BCDOCNO, 6)'), substr($cekTempData['NO_DAFTAR'], 0, 6))
                        ->where('BCTYPE', $cekTempData['TYPE_BC'])
                        ->where('BCDOCDT', $cekTempData['TGL_DAFTAR']);

                    $baseDocUpdate = ITINVOutgoing::where(DB::raw('LEFT(BCDOCNO, 6)'), substr($cekTempData['NO_DAFTAR'], 0, 6))
                        ->where('BCTYPE', $cekTempData['TYPE_BC'])
                        ->where('BCDOCDT', $cekTempData['TGL_DAFTAR']);

                    $jumlahInv = (clone $baseDoc)->where('INVNO', $row['nomor_dokumen'])->count();
                    $jumlahDoc = (clone $baseDoc)->where('DOCNO', $row['nomor_dokumen'])->count();
                }
                $time_end = microtime(true);


                $execution_time = ($time_end - $time_start) / 60;

                logger('Dokumen fetch: Total time cek Data on IT Inventory: ' . $execution_time);


                $time_start = microtime(true);
                
                // Invoice
                if ($row['kode_dokumen'] == 380 && $jumlahInv === 0) {
                    if ($this->incout == 'INC') {
                        $cekIncoming = (clone $baseDoc)
                            ->whereNull(DB::raw("NULLIF(HHEINVNO, '')"))
                            ->get();

                        if (count($cekIncoming) > 0) {
                            foreach ($cekIncoming->pluck('ITMCD') as $key => $valueItm) {
                                (clone $baseDocUpdate)
                                    ->where('ITMCD', $valueItm)
                                    ->update([
                                        'HHEINVNO' => $row['nomor_dokumen'],
                                        'PENGIRIM' => $cekTempData['PENGIRIM']
                                    ]);
                            }
                        }
                    } else {
                        $cekOutgoing = (clone $baseDoc)
                            ->first();

                        $explodeData = [];
                        if (!empty($cekOutgoing->INVNO)) {
                            $explodeData = explode(";", $cekOutgoing->INVNO);
                        }

                        $explodeData[] = $row['nomor_dokumen'];

                        (clone $baseDocUpdate)
                            ->update([
                                'INVNO' => count($explodeData) > 1 ? implode(";", $explodeData): $explodeData[0]
                            ]);

                        // if (count($cekOutgoing) > 0) {
                        //     foreach ($cekOutgoing->pluck('ITMCD') as $key => $valueItm) {
                        //         (clone $baseDocUpdate)
                        //             ->where('ITMCD', $valueItm)
                        //             ->update([
                        //                 'INVNO' => $row['nomor_dokumen']
                        //             ]);
                        //     }
                        // } else {
                        //     $cekLatest = (clone $baseDoc)->first();

                        //     if (!empty($cekLatest)) {
                        //         foreach ($cekOutgoing->pluck('ITMCD') as $key => $valueItm) {
                        //             $explodeData = explode($cekLatest, ";");
                        //             $explodeData[] = $row['nomor_dokumen'];

                        //             (clone $baseDocUpdate)
                        //                 ->where('ITMCD', $valueItm)
                        //                 ->update([
                        //                     'INVNO' => implode(";", $explodeData)
                        //                 ]);
                        //         }
                        //     };
                        // }
                    }
                }

                // Tax Invoice
                if ($row['kode_dokumen'] == 388 && $jumlahInv === 0) {
                    if ($this->incout == 'INC') {
                        $time_start_ins = microtime(true);
                        $cekIncoming = (clone $baseDoc)
                            ->whereNull(DB::raw("NULLIF(TAXINV, '')"))
                            ->get();
                        $time_end_ins = microtime(true);
                        $execution_time = ($time_end_ins - $time_start_ins) / 60;

                        logger($cekIncoming);
                        logger('Dokumen fetch: Total time cek Data if TAXINV in null on IT Inventory: ' . $execution_time);

                        if (count($cekIncoming) > 0) {
                            $time_start_ins = microtime(true);
                            ITINVIncoming::where(DB::raw('LEFT(BCDOCNO, 6)'), substr($cekTempData['NO_DAFTAR'], 0, 6))
                                ->where('BCTYPE', $cekTempData['TYPE_BC'])
                                ->where('BCDOCDT', $cekTempData['TGL_DAFTAR'])
                                ->whereIn('ITMCD', $cekIncoming->pluck('ITMCD'))
                                ->update([
                                    'TAXINV' => $row['nomor_dokumen']
                                ]);

                            $time_end_ins = microtime(true);
                            $execution_time = ($time_end_ins - $time_start_ins) / 60;

                            logger('Dokumen fetch: Total time update if TAXINV in null on IT Inventory: ' . $execution_time);

                        }
                    }
                }

                // sj
                if (($row['kode_dokumen'] == 640 || $row['kode_dokumen'] == 630) && $jumlahDoc === 0) {
                    logger('Inv ' . $row['nomor_dokumen']);
                    logger('Jumlah Doc ' . $jumlahDoc);
                    if ($this->incout == 'INC') {
                        $cekIncoming = (clone $baseDoc)
                            ->whereNull(DB::raw("NULLIF(DOCNO, '')"))
                            ->get();

                        if (count($cekIncoming) > 0) {
                            (clone $baseDocUpdate)
                                ->whereIn('ITMCD', $cekIncoming->pluck('ITMCD'))
                                ->update([
                                    'DOCNO' => $row['nomor_dokumen'],
                                    'PENGIRIM' => $cekTempData['PENGIRIM']
                                ]);
                        }
                    } else {
                        $cekOutgoing = (clone $baseDoc)
                            ->whereNull(DB::raw("NULLIF(DOCNO, '')"))
                            ->get();

                        if (count($cekOutgoing) > 0) {
                            (clone $baseDocUpdate)
                                ->whereIn('ITMCD', $cekOutgoing->pluck('ITMCD'))
                                ->update([
                                    'DOCNO' => $row['nomor_dokumen']
                                ]);
                        }
                    }
                }

                // EX-BC
                if (($row['kode_dokumen'] == 33) && $jumlahInv === 0) {
                    if ($this->incout == 'OUT') {
                        $cekOutgoing = (clone $baseDoc)
                            ->where('BCTYPE', 'P3BET')
                            ->whereNull('BC23BCTYPE');

                        if ((clone $cekOutgoing)->count() > 0) {
                            $listItem = (clone $cekOutgoing)->pluck('ITMCD');
                            logger(json_encode($listItem));
                            $listUpdated = [
                                'BC23BCTYPE' => $row['kode_dokumen'] == 33 ? 'BC3.3' : 'BC1.6',
                                'BC33DOCNO' => $row['kode_dokumen'] == 33 ? $row['nomor_dokumen'] : NULL,
                                'BC33DOCDT' => $row['kode_dokumen'] == 33 ? $row['tanggal_dokumen'] : NULL,
                            ];

                            logger(json_encode($listUpdated));

                            (clone $baseDocUpdate)
                                ->whereIn('ITMCD', $cekOutgoing->pluck('ITMCD'))
                                ->update($listUpdated);
                        }
                    }
                }

                $time_end = microtime(true);

                // logger("Dokumen fetch: Total time Update data on IT Inventory: {$execution_time}");
            }
        }

        logger(json_encode($row));
    }
}
