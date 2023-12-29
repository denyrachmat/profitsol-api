<?php

namespace App\Imports\STXI\LOG\Ceisa40;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;

use App\Models\STXI\LOG\ITINVIncoming;
use App\Models\STXI\LOG\ITINVOutgoing;
use App\Models\STXI\LOG\ITINVUploadTemp;

class ImportBarang implements ToModel, WithHeadingRow
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
        if (!empty($cekTempData)) {
            if ($this->incout == 'INC') {
                $cekIncoming = ITINVIncoming::where('BCDOCNO', 'LIKE', $cekTempData['NO_DAFTAR'] . '%')
                    ->where('BCTYPE', $cekTempData['TYPE_BC'])
                    ->where('BCDOCDT', $cekTempData['TGL_DAFTAR'])
                    ->where('ITMCD', trim($row['kode_barang']))
                    ->first();

                $getHSCode = DB::connection('sqlsrv_log')->table('V_HSCODE')->where('ITEM_CODE', $row['kode_barang'])->first();
                if (!empty($cekIncoming)) {

                    ITINVIncoming::where("BCDOCNO", 'LIKE', $cekTempData['NO_DAFTAR'] . '%')
                        ->where('BCTYPE', $cekTempData['TYPE_BC'])
                        ->where('BCDOCDT', $cekTempData["TGL_DAFTAR"])
                        ->where('ITMCD', trim($row['kode_barang']))
                        ->update([
                            'PRICE' => round((int) $row['cif'] / (int) $row['jumlah_satuan'], 4),
                            'TTLAMOUNT' => round((int) $row['cif'], 4),
                            'HSCODE' => $row['hs']
                        ]);
                } else {
                    $UOM = 'PIECE';
                    if ($row['kode_satuan'] !== 'PCE') {
                        $UOM = $row['kode_satuan'];
                    }

                    ITINVIncoming::updateOrCreate([
                        'BCTYPE' => $cekTempData['TYPE_BC'],
                        'BCDOCNO' => $cekTempData['NO_DAFTAR'],
                        'BCDOCDT' => $cekTempData['TGL_DAFTAR'],
                        'ITMCD' => trim($row['kode_barang']),
                    ], [
                        'LOCCD' => 'STX-I',
                        'BCTYPE' => $cekTempData['TYPE_BC'],
                        'BCDOCNO' => $cekTempData['NO_DAFTAR'],
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
            } else {
                $cekOutgoing = ITINVOutgoing::where('BCDOCNO', 'LIKE', $cekTempData['NO_DAFTAR'] . '%')
                    ->where('BCTYPE', $cekTempData['TYPE_BC'])
                    ->where('BCDOCDT', $cekTempData['TGL_DAFTAR'])
                    ->where('ITMCD', trim($row['kode_barang']))
                    ->first();

                if (!empty($cekOutgoing)) {
                    ITINVOutgoing::where("BCDOCNO", 'LIKE', $cekTempData['NO_DAFTAR'] . '%')
                        ->where('BCTYPE', $cekTempData['TYPE_BC'])
                        ->where('BCDOCDT', $cekTempData["TGL_DAFTAR"])
                        ->where('ITMCD', trim($row['kode_barang']))
                        ->update([
                            'PRICE' => round((int) $row['cif'] / (int) $row['jumlah_satuan'], 4),
                            'TTLAMOUNT' => round((int) $row['cif'], 4),
                        ]);
                } else {
                    $UOM = 'PIECE';
                    if ($row['kode_satuan'] !== 'PCE') {
                        $UOM = $row['kode_satuan'];
                    }

                    ITINVOutgoing::updateOrCreate([
                        'BCTYPE' => $cekTempData['TYPE_BC'],
                        'BCDOCNO' => $cekTempData['NO_DAFTAR'],
                        'BCDOCDT' => $cekTempData['TGL_DAFTAR'],
                        'ITMCD' => trim($row['kode_barang']),
                    ], [
                        'LOCCD' => 'STX-I',
                        'BCTYPE' => $cekTempData['TYPE_BC'],
                        'BCDOCNO' => $cekTempData['NO_DAFTAR'],
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
                        'PENGIRIM' => $cekTempData['PENGIRIM'],
                        'WMSLOC' => '',
                        'HSCODE' => $row['hs']
                    ]);
                }
            }
        }
    }
}
