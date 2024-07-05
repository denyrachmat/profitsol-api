<?php

namespace App\Imports\STXI\LOG\Ceisa40;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Facades\DB;

use App\Models\STXI\LOG\ITINVIncoming;
use App\Models\STXI\LOG\ITINVOutgoing;
use App\Models\STXI\LOG\ITINVUploadTemp;
use App\Models\STXI\CEISA40\viewCeisaRespon;

class ImportBarang implements ToModel, WithHeadingRow, SkipsEmptyRows
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
        logger('barang start');
        ini_set("memory_limit", "3G");
        if (!empty(trim($row['nomor_aju']))) {
            $cekTempData = ITINVUploadTemp::where('NO_AJU', $row['nomor_aju'])->first();
            if (!empty($cekTempData)) {
                $noDaftar = substr($cekTempData['NO_DAFTAR'], 0, 6);
                $getHSCode = DB::connection('sqlsrv_itinv')->table('VIEW_MITM_TBL')->where('MITM_ITMCD', $row['kode_barang'])->first();
                if ($this->incout == 'INC') {
                    $cekIncoming = ITINVIncoming::where('BCDOCNO', 'LIKE', $noDaftar . '%')
                        ->where('BCTYPE', $cekTempData['TYPE_BC'])
                        ->where('BCDOCDT', $cekTempData['TGL_DAFTAR'])
                        ->where('ITMCD', trim($row['kode_barang']))
                        ->first();

                    if (!empty($cekIncoming)) {
                        ITINVIncoming::where("BCDOCNO", 'LIKE', $noDaftar . '%')
                            ->where('BCTYPE', $cekTempData['TYPE_BC'])
                            ->where('BCDOCDT', $cekTempData["TGL_DAFTAR"])
                            ->where('ITMCD', trim($row['kode_barang']))
                            ->update([
                                'PRICE' => $row['cif'] == 0
                                ? round((int) $row['harga_penyerahan'] / (int) $row['jumlah_satuan'], 4)
                                : round((int) $row['cif'] / (int) $row['jumlah_satuan'], 4),
                                'TTLAMOUNT' => $row['cif'] == 0
                                ? round((int) $row['harga_penyerahan'], 4)
                                : round((int) $row['cif'], 4),
                                'HSCODE' => $row['hs'],
                                'ITMD1' => !empty($getHSCode) ? $getHSCode->MITM_ITMD1 : trim($row['uraian']),
                                'SPTNO' => !empty($getHSCode) ? $getHSCode->MITM_SPTNO : '',
                                'PENGIRIM' => $cekTempData['PENGIRIM']
                            ]);
                    } else {
                        $UOM = 'PIECE';
                        if ($row['kode_satuan'] !== 'PCE') {
                            $UOM = $row['kode_satuan'];
                        }

                        $cekBCStatus = viewCeisaRespon::where('NOMOR_DAFTAR', $noDaftar)->where('TGL_DAFTAR', $cekTempData['TGL_DAFTAR'])->first();

                        if ($cekBCStatus->STAT_MEGABCDOC == 1) {
                            $cekItemMega = DB::connection('sqlsrv_itinv')->table('VIEW_MITM_TBL')->where('MITM_ITMCD', $row['kode_barang'])->first();

                            ITINVIncoming::updateOrCreate([
                                'BCTYPE' => $cekTempData['TYPE_BC'],
                                'BCDOCNO' => $noDaftar,
                                'BCDOCDT' => $cekTempData['TGL_DAFTAR'],
                                'ITMCD' => trim($row['kode_barang']),
                            ], [
                                'LOCCD' => empty($cekItemMega) ? 'STX-I' : '',
                                'BCTYPE' => $cekTempData['TYPE_BC'],
                                'BCDOCNO' => $noDaftar,
                                'BCDOCDT' => $cekTempData['TGL_DAFTAR'],
                                'BSGRP' => 'LAIN NYA',
                                'DOCCD' => '',
                                'DOCNO' => '',
                                'HHEINVNO' => '',
                                'ISUDT' => $cekTempData['TGL_DAFTAR'],
                                'ITMCD' => trim($row['kode_barang']),
                                'ITMD1' => $row['uraian'],
                                'SPTNO' => $row['tipe'],
                                'UOM' => $UOM,
                                'TTLQTY' => $row['jumlah_satuan'],
                                'CURCD' => $cekTempData['CURR'],
                                'PRICE' => round((int) $row['cif'] / (int) $row['jumlah_satuan'], 4),
                                'TTLAMOUNT' => round((int) $row['cif'], 4),
                                'TAXINV' => '',
                                'SUPNM' => $cekTempData['SUPPL'],
                                'PENGIRIM' => $cekTempData['PENGIRIM'],
                                'WMSLOC' => '',
                                'HSCODE' => $row['hs']
                            ]);
                        } else {
                            $cekItemMega = DB::connection('sqlsrv_itinv')->table('VIEW_MITM_TBL')->where('MITM_ITMCD', $row['kode_barang'])->first();

                            if (empty($cekItemMega)) {
                                ITINVIncoming::updateOrCreate([
                                    'BCTYPE' => $cekTempData['TYPE_BC'],
                                    'BCDOCNO' => $noDaftar,
                                    'BCDOCDT' => $cekTempData['TGL_DAFTAR'],
                                    'ITMCD' => trim($row['kode_barang']),
                                ], [
                                    'LOCCD' => empty($cekItemMega) ? 'STX-I' : '',
                                    'BCTYPE' => $cekTempData['TYPE_BC'],
                                    'BCDOCNO' => $noDaftar,
                                    'BCDOCDT' => $cekTempData['TGL_DAFTAR'],
                                    'BSGRP' => 'LAIN NYA',
                                    'DOCCD' => '',
                                    'DOCNO' => '',
                                    'HHEINVNO' => '',
                                    'ISUDT' => $cekTempData['TGL_DAFTAR'],
                                    'ITMCD' => trim($row['kode_barang']),
                                    'ITMD1' => $row['uraian'],
                                    'SPTNO' => $row['tipe'],
                                    'UOM' => $UOM,
                                    'TTLQTY' => $row['jumlah_satuan'],
                                    'CURCD' => $cekTempData['CURR'],
                                    'PRICE' => round((int) $row['cif'] / (int) $row['jumlah_satuan'], 4),
                                    'TTLAMOUNT' => round((int) $row['cif'], 4),
                                    'TAXINV' => '',
                                    'SUPNM' => $cekTempData['SUPPL'],
                                    'PENGIRIM' => $cekTempData['PENGIRIM'],
                                    'WMSLOC' => '',
                                    'HSCODE' => $row['hs']
                                ]);
                            }
                        }
                    }
                } else {
                    $cekOutgoing = ITINVOutgoing::where('BCDOCNO', 'LIKE', $noDaftar . '%')
                        ->where('BCTYPE', $cekTempData['TYPE_BC'])
                        ->where('BCDOCDT', $cekTempData['TGL_DAFTAR'])
                        ->where('ITMCD', trim($row['kode_barang']))
                        ->first();

                    if (!empty($cekOutgoing)) {
                        ITINVOutgoing::where("BCDOCNO", 'LIKE', $noDaftar . '%')
                            ->where('BCTYPE', $cekTempData['TYPE_BC'])
                            ->where('BCDOCDT', $cekTempData["TGL_DAFTAR"])
                            ->where('ITMCD', trim($row['kode_barang']))
                            ->update([
                                'PRICE' => $row['cif'] == 0
                                ? round((int) $row['harga_penyerahan'] / (int) $row['jumlah_satuan'], 4)
                                : round((int) $row['cif'] / (int) $row['jumlah_satuan'], 4),
                                'TTLAMOUNT' => $row['cif'] == 0
                                ? round((int) $row['harga_penyerahan'], 4)
                                : round((int) $row['cif'], 4),
                                'CUSNM' => $cekTempData['PENERIMA'],
                                'ITMD1' => !empty($getHSCode) ? $getHSCode->MITM_ITMD1 : trim($row['uraian']),
                                'SPTNO' => !empty($getHSCode) ? $getHSCode->MITM_SPTNO : ''
                            ]);
                    } else {
                        $UOM = 'PIECE';
                        if ($row['kode_satuan'] !== 'PCE') {
                            $UOM = $row['kode_satuan'];
                        }

                        ITINVOutgoing::updateOrCreate([
                            'BCTYPE' => $cekTempData['TYPE_BC'],
                            'BCDOCNO' => $noDaftar,
                            'BCDOCDT' => $cekTempData['TGL_DAFTAR'],
                            'ITMCD' => trim($row['kode_barang']),
                        ], [
                            'LOCCD' => 'STX-I',
                            'BCTYPE' => $cekTempData['TYPE_BC'],
                            'BCDOCNO' => $noDaftar,
                            'BCDOCDT' => $cekTempData['TGL_DAFTAR'],
                            'BSGRP' => 'LAIN NYA',
                            'DOCCD' => '',
                            'DOCNO' => '',
                            'HHEINVNO' => '',
                            'ISUDT' => $cekTempData['TGL_DAFTAR'],
                            'ITMCD' => trim($row['kode_barang']),
                            'ITMD1' => $row['uraian'],
                            'SPTNO' => $row['tipe'],
                            'UOM' => $UOM,
                            'TTLQTY' => $row['jumlah_satuan'],
                            'CURCD' => $cekTempData['CURR'],
                            'PRICE' => round((int) $row['cif'] / (int) $row['jumlah_satuan'], 4),
                            'TTLAMOUNT' => round((int) $row['cif'], 4),
                            'TAXINV' => '',
                            'CUSNM' => $cekTempData['PENERIMA'],
                            'WMSLOC' => '',
                            'HSCODE' => $row['hs']
                        ]);
                    }
                }
            }
        }

        logger(json_encode($row));
    }
}
