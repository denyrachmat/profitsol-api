<?php

namespace App\Imports\STXI\LOG\Ceisa40;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

use App\Models\STXI\LOG\ITINVIncoming;
use App\Models\STXI\LOG\ITINVOutgoing;
use App\Models\STXI\LOG\ITINVUploadTemp;

class ImportHeader implements ToModel, WithHeadingRow
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

        $kodeDokumen = '';

        if ($this->incout === 'INC') {
            switch ($row['kode_dokumen']) {
                case 16:
                    $kodeDokumen = 'BC1.6';
                    break;
                case 27:
                    $kodeDokumen = 'BC2.7I';
                    break;
                default:
                    $kodeDokumen = 'BC4.0';
                    break;
            }
        } else {
            switch ($row['kode_dokumen']) {
                case 33:
                    $kodeDokumen = 'BC3.3';
                    break;
                case 27:
                    $kodeDokumen = 'BC2.7';
                    break;
                case 28:
                    $kodeDokumen = 'BC2.8';
                    break;
                default:
                    $kodeDokumen = 'P3BET';
                    break;
            }
        }

        if ($this->incout === 'INC') {
            $cekData = ITINVIncoming::where("BCDOCNO", 'LIKE', $row["nomor_daftar"] . '%')
                ->where('BCTYPE', $kodeDokumen)
                ->where('BCDOCDT', $row["tanggal_daftar"])
                ->first();

            if (!empty($cekData)) {
                ITINVIncoming::where("BCDOCNO", 'LIKE', $row["nomor_daftar"] . '%')
                    ->where('BCTYPE', $kodeDokumen)
                    ->where('BCDOCDT', $row["tanggal_daftar"])
                    ->update([
                        'CURCD' => $row['kode_valuta'],
                    ]);

                ITINVUploadTemp::updateOrCreate([
                    'NO_AJU' => $row['nomor_aju'],
                    'NO_DAFTAR' => $cekData->BCDOCNO,
                    'TYPE_BC' => $kodeDokumen,
                    'STATE_FLG' => $this->incout
                ], [
                    'NO_AJU' => $row['nomor_aju'],
                    'NO_DAFTAR' => $cekData->BCDOCNO,
                    'TGL_DAFTAR' => $row['tanggal_daftar'],
                    'TYPE_BC' => $kodeDokumen,
                    'CURR' => $row['kode_valuta'],
                    'STATE_FLG' => $this->incout
                ]);
            } else {
                ITINVUploadTemp::updateOrCreate([
                    'NO_AJU' => $row['nomor_aju'],
                    'NO_DAFTAR' => $row["nomor_daftar"],
                    'TYPE_BC' => $kodeDokumen,
                    'STATE_FLG' => $this->incout
                ], [
                    'NO_AJU' => $row['nomor_aju'],
                    'NO_DAFTAR' => $row["nomor_daftar"],
                    'TGL_DAFTAR' => $row['tanggal_daftar'],
                    'TYPE_BC' => $kodeDokumen,
                    'CURR' => $row['kode_valuta'],
                    'STATE_FLG' => $this->incout
                ]);
            }
        } else {
            logger([$row['kode_dokumen'], $kodeDokumen]);
            $cekData = ITINVOutgoing::where("BCDOCNO", $row["nomor_daftar"])
                ->where('BCTYPE', $kodeDokumen)
                ->where('BCDOCDT', $row["tanggal_daftar"])
                ->first();

            if (!empty($cekData)) {
                ITINVOutgoing::where("BCDOCNO", $row["nomor_daftar"])
                    ->where('BCTYPE', $kodeDokumen)
                    ->where('BCDOCDT', $row["tanggal_daftar"])
                    ->update([
                        'CURCD' => empty($row['kode_valuta']) ? $row['kode_valuta'] : 'USD',
                    ]);

                ITINVUploadTemp::updateOrCreate([
                    'NO_AJU' => $row['nomor_aju'],
                    'NO_DAFTAR' => $row['nomor_daftar'],
                    'TYPE_BC' => $kodeDokumen,
                    'STATE_FLG' => $this->incout
                ], [
                    'NO_AJU' => $row['nomor_aju'],
                    'NO_DAFTAR' => $cekData->BCDOCNO,
                    'TGL_DAFTAR' => $row['tanggal_daftar'],
                    'TYPE_BC' => $kodeDokumen,
                    'CURR' => empty($row['kode_valuta']) ? $row['kode_valuta'] : 'USD',
                    'STATE_FLG' => $this->incout
                ]);
            } else {
                ITINVUploadTemp::updateOrCreate([
                    'NO_AJU' => $row['nomor_aju'],
                    'NO_DAFTAR' => $row["nomor_daftar"],
                    'TYPE_BC' => $kodeDokumen,
                    'STATE_FLG' => $this->incout
                ], [
                    'NO_AJU' => $row['nomor_aju'],
                    'NO_DAFTAR' => $row["nomor_daftar"],
                    'TGL_DAFTAR' => $row['tanggal_daftar'],
                    'TYPE_BC' => $kodeDokumen,
                    'CURR' => $row['kode_valuta'],
                    'STATE_FLG' => $this->incout
                ]);
            }

            // $redis->publish('message', json_encode([
            //     'app' => 'log',
            //     'status' => 'positive',
            //     'message' => 'Outgoing on progress added',
            //     'data' => $row
            // ]));
        }
    }
}
