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

class ImportHeader implements ToModel, WithHeadingRow, SkipsEmptyRows
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
        logger('header start');
        ini_set("memory_limit", "3G");

        if (!array_filter($row)) {
            return null;
        }

        if (!empty(trim($row['kode_dokumen']))) {

            $kodeDokumen = '';

            if ($this->incout === 'INC') {
                switch ($row['kode_dokumen']) {
                    case 16:
                        $kodeDokumen = 'BC1.6';
                        break;
                    case 27:
                        $kodeDokumen = 'BC2.7I';
                        break;
                    case 20:
                        $kodeDokumen = 'BC2.0';
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
                    case 41:
                        $kodeDokumen = 'BC4.1';
                        break;
                    default:
                        $kodeDokumen = 'P3BET';
                        break;
                }
            }

            if ($this->incout === 'INC') {
                ITINVIncoming::where("BCDOCNO", 'LIKE', $row["nomor_daftar"] . '%')
                    ->where('BCTYPE', $kodeDokumen)
                    ->where('BCDOCDT', $row["tanggal_daftar"])
                    ->delete();

                $cekData = ITINVIncoming::where("BCDOCNO", 'LIKE', $row["nomor_daftar"] . '%')
                    ->where('BCTYPE', $kodeDokumen)
                    ->where('BCDOCDT', $row["tanggal_daftar"])
                    ->first();

                if (!empty($cekData)) {
                    // Check is it real BG LAIN NYA
                    if (empty($cekData->BSGRP) || $cekData->BSGRP === 'LAIN NYA') {
                        $cekHeader = DB::connection('sqlsrv_mega_db')
                            ->table('Z_STXI_VW_CBCDOC')
                            ->where('BCDOCNO', $row["nomor_daftar"])
                            ->first();

                        ITINVIncoming::where("BCDOCNO", 'LIKE', $row["nomor_daftar"] . '%')
                            ->where('BCTYPE', $kodeDokumen)
                            ->where('BCDOCDT', $row["tanggal_daftar"])
                            ->update([
                                'LOCCD' => empty($cekHeader) ? 'STX-I' : (
                                    !empty($cekHeader->FIFO_LOCCD)
                                    ? $cekHeader->FIFO_LOCCD
                                    : $cekHeader->CBCDOC_WHSCD
                                ),
                                'BSGRP' => empty($cekHeader) ? 'STX-I' : (
                                    !empty($cekHeader->FIFO_BSGRP)
                                    ? $cekHeader->FIFO_BSGRP
                                    : $cekHeader->CBCDOC_BSGRP
                                ),
                                'DOCCD' => $cekHeader->CBCDOC_DOCCD,
                                'BCTYPE' => $kodeDokumen,
                                'CURCD' => !empty($row['kode_valuta']) ? $row['kode_valuta'] : (
                                    $row['kode_dokumen'] == 40
                                    ? 'IDR'
                                    : 'USD'
                                ),
                            ]);
                    } else {
                        ITINVIncoming::where("BCDOCNO", 'LIKE', $row["nomor_daftar"] . '%')
                            ->where('BCTYPE', $kodeDokumen)
                            ->where('BCDOCDT', $row["tanggal_daftar"])
                            ->update([
                                'CURCD' => !empty($row['kode_valuta']) ? $row['kode_valuta'] : (
                                    $row['kode_dokumen'] == 40
                                    ? 'IDR'
                                    : 'USD'
                                ),
                            ]);
                    }

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
                        'CURR' => !empty($row['kode_valuta']) ? $row['kode_valuta'] : (
                            $row['kode_dokumen'] == 40
                            ? 'IDR'
                            : 'USD'
                        ),
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
                        'CURR' => !empty($row['kode_valuta']) ? $row['kode_valuta'] : (
                            $row['kode_dokumen'] == 40
                            ? 'IDR'
                            : 'USD'),
                        'STATE_FLG' => $this->incout
                    ]);
                }
            } else {
                ITINVOutgoing::where("BCDOCNO", $row["nomor_daftar"])
                    ->where('BCTYPE', $kodeDokumen)
                    ->where('BCDOCDT', $row["tanggal_daftar"])
                    ->delete();

                $cekData = ITINVOutgoing::where("BCDOCNO", $row["nomor_daftar"])
                    ->where('BCTYPE', $kodeDokumen)
                    ->where('BCDOCDT', $row["tanggal_daftar"])
                    ->first();

                if (!empty($cekData)) {
                    if (empty($cekData->BSGRP) || $cekData->BSGRP === 'LAIN NYA') {
                        $cekHeader = DB::connection('sqlsrv_mega_db')
                            ->table('Z_STXI_VW_CBCDOC')
                            ->where('CBCDOC_BCDOCNO', $row["nomor_daftar"])
                            ->first();


                        ITINVOutgoing::where("BCDOCNO", 'LIKE', $row["nomor_daftar"] . '%')
                            ->where('BCTYPE', $kodeDokumen)
                            ->where('BCDOCDT', $row["tanggal_daftar"])
                            ->update([
                                'LOCCD' => empty($cekHeader) ? 'STX-I' : (
                                    !empty($cekHeader->FIFO_LOCCD)
                                    ? $cekHeader->FIFO_LOCCD
                                    : $cekHeader->CBCDOC_WHSCD
                                ),
                                'BSGRP' => empty($cekHeader) ? 'STX-I' : (
                                    !empty($cekHeader->FIFO_BSGRP)
                                    ? $cekHeader->FIFO_BSGRP
                                    : $cekHeader->CBCDOC_BSGRP
                                ),
                                'DOCCD' => $cekHeader->CBCDOC_DOCCD,
                                'BCTYPE' => $kodeDokumen,
                                'CURCD' => !empty($row['kode_valuta']) ? $row['kode_valuta'] : (
                                    $row['kode_dokumen'] == 41
                                    ? 'IDR'
                                    : 'USD'
                                ),
                            ]);
                    } else {
                        ITINVOutgoing::where("BCDOCNO", $row["nomor_daftar"])
                            ->where('BCTYPE', $kodeDokumen)
                            ->where('BCDOCDT', $row["tanggal_daftar"])
                            ->update([
                                'CURCD' => !empty($row['kode_valuta']) ? $row['kode_valuta'] : (
                                    $row['kode_dokumen'] == 41
                                    ? 'IDR'
                                    : 'USD'
                                ),
                            ]);
                    }

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
                        'CURR' => !empty($row['kode_valuta']) ? $row['kode_valuta'] : (
                            $row['kode_dokumen'] == 41
                            ? 'IDR'
                            : 'USD'
                        ),
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
                        'CURR' => !empty($row['kode_valuta']) ? $row['kode_valuta'] : (
                            $row['kode_dokumen'] == 41
                            ? 'IDR'
                            : 'USD'
                        ),
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

        logger(json_encode($row));
    }
}
