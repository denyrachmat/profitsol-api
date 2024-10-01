<?php

namespace App\Imports\STXI\LOG\Ceisa40;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Facades\DB;
use Redis;

use App\Models\STXI\LOG\ITINVIncoming;
use App\Models\STXI\LOG\ITINVOutgoing;
use App\Models\STXI\LOG\ITINVUploadTemp;
use App\Models\STXI\CEISA40\viewCeisaRespon;

class ImportBarangNew implements ToModel, WithHeadingRow, SkipsEmptyRows
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
        if (!empty(trim($row['nomor_aju']))) {
            $cekTempData = ITINVUploadTemp::where('NO_AJU', $row['nomor_aju'])->first();
            if (!empty($cekTempData)) {
                $noDaftar = substr($cekTempData['NO_DAFTAR'], 0, 6);
                $UOM = 'PIECE';
                if ($row['kode_satuan'] !== 'PCE') {
                    $UOM = $row['kode_satuan'];
                }

                $data = [
                    'LOCCD' => 'STX-I',
                    'BCTYPE' => '',
                    'BCDOCNO' => '',
                    'BSGRP' => '',
                    'DOCCD' => 'LAIN NYA',
                    'DOCNO' => '',
                    'HHEINVNO' => '',
                    'ISUDT' => '',
                    'ITMCD' => trim($row['kode_barang']),
                    'ITMD1' => $row['uraian'],
                    'SPTNO' => $row['tipe'],
                    'UOM' => $UOM,
                    'TTLQTY' => $row['jumlah_satuan'],
                    'CURCD' => '',
                    'PRICE' => round((int) $row['cif'] / (int) $row['jumlah_satuan'], 4),
                    'TTLAMOUNT' => round((int) $row['cif'], 4),
                    'TAXINV' => '',
                    'SUPNM' => '',
                    'PENGIRIM' => '',
                    'WMSLOC' => '',
                    'HSCODE' => $row['hs']
                ];

                if ($this->incout == 'INC') {
                    $insert = ITINVIncoming::create($data);
                } else {
                    $insert = ITINVOutgoing::create($data);
                }
            }
        }
    }
}
