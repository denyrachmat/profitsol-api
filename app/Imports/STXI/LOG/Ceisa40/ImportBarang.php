<?php

namespace App\Imports\STXI\LOG\Ceisa40;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

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
                    $UOM = 'PIECE';
                    if ($row['kode_satuan'] !== 'PCE') {
                        $UOM = $row['kode_satuan'];
                    }

                    $cekBCStatus = viewCeisaRespon::where('NOMOR_DAFTAR', $noDaftar)->where('TGL_DAFTAR', $cekTempData['TGL_DAFTAR'])->first();

                    $cekItemMega = DB::connection('sqlsrv_itinv')->table('VIEW_MITM_TBL')->where('MITM_ITMCD', $row['kode_barang'])->first();
                    if ($cekBCStatus->STAT_MEGABCDOC == 1 && !empty($cekItemMega)) {
                        $cekHeaderMega = DB::connection('sqlsrv_mega_db')
                            ->table('Z_STXI_VW_CBCDOC')
                            ->where('CBCDOC_BCDOCNO', 'like',$noDaftar.'%')
                            ->where('CBCDOC_BCDOCDT', $cekTempData['TGL_DAFTAR'])
                            ->where('CBCDOCPRC_ITMCD', trim($row['kode_barang']))
                            ->where('DB', $cekItemMega->DB)
                            ->first();

                        $insert = ITINVIncoming::create([
                            'LOCCD' => empty($cekHeaderMega) ? 'STX-I' : (
                                !empty($cekHeaderMega->FIFO_LOCCD)
                                ? $cekHeaderMega->FIFO_LOCCD
                                : $cekHeaderMega->CBCDOC_WHSCD
                            ),
                            'BCTYPE' => $cekTempData['TYPE_BC'],
                            'BCDOCNO' => $noDaftar,
                            'BCDOCDT' => $cekTempData['TGL_DAFTAR'],
                            'BSGRP' => str_contains(trim($row['kode_barang']), 'pallet') || empty($cekHeaderMega) ? 'LAIN NYA' : (
                                !empty($cekHeaderMega->FIFO_BSGRP)
                                ? $cekHeaderMega->FIFO_BSGRP
                                : $cekHeaderMega->CBCDOC_BSGRP
                            ),
                            'DOCCD' => str_contains(trim($row['kode_barang']), 'pallet') ? $cekHeaderMega->CBCDOC_DOCCD : '',
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

                        // $insert = ITINVIncoming::updateOrCreate([
                        //     'BCTYPE' => $cekTempData['TYPE_BC'],
                        //     'BCDOCNO' => $noDaftar,
                        //     'BCDOCDT' => $cekTempData['TGL_DAFTAR'],
                        //     'ITMCD' => trim($row['kode_barang']),
                        //     // 'TTLQTY' => $row['jumlah_satuan'],
                        // ], [
                        //     'LOCCD' => empty($cekHeaderMega) ? 'STX-I' : (
                        //         !empty($cekHeaderMega->FIFO_LOCCD)
                        //         ? $cekHeaderMega->FIFO_LOCCD
                        //         : $cekHeaderMega->CBCDOC_WHSCD
                        //     ),
                        //     'BCTYPE' => $cekTempData['TYPE_BC'],
                        //     'BCDOCNO' => $noDaftar,
                        //     'BCDOCDT' => $cekTempData['TGL_DAFTAR'],
                        //     'BSGRP' => empty($cekHeaderMega) ? 'LAIN NYA' : (
                        //         !empty($cekHeaderMega->FIFO_BSGRP)
                        //         ? $cekHeaderMega->FIFO_BSGRP
                        //         : $cekHeaderMega->CBCDOC_BSGRP
                        //     ),
                        //     'DOCCD' => $cekHeaderMega->CBCDOC_DOCCD,
                        //     'DOCNO' => '',
                        //     'HHEINVNO' => '',
                        //     'ISUDT' => $cekTempData['TGL_DAFTAR'],
                        //     'ITMCD' => trim($row['kode_barang']),
                        //     'ITMD1' => $row['uraian'],
                        //     'SPTNO' => $row['tipe'],
                        //     'UOM' => $UOM,
                        //     'TTLQTY' => $row['jumlah_satuan'],
                        //     'CURCD' => $cekTempData['CURR'],
                        //     'PRICE' => round((int) $row['cif'] / (int) $row['jumlah_satuan'], 4),
                        //     'TTLAMOUNT' => round((int) $row['cif'], 4),
                        //     'TAXINV' => '',
                        //     'SUPNM' => $cekTempData['SUPPL'],
                        //     'PENGIRIM' => $cekTempData['PENGIRIM'],
                        //     'WMSLOC' => '',
                        //     'HSCODE' => $row['hs']
                        // ]);

                        Redis::publish('portalv2', json_encode([
                            'app' => 'it_inv_checker',
                            'message' => $noDaftar . ' on date bc : ' . $cekTempData["TGL_DAFTAR"] . ' - INC Item Exists on updated',
                            'type' => 'info',
                            'status' => 'progress_bc_sync_item_inc_not_exists_w_mega',
                            'data' => [
                                'NOMOR_DAFTAR' => $noDaftar,
                                'TGL_DAFTAR' => $cekTempData["TGL_DAFTAR"],
                                'updatedItem' => trim($row['kode_barang']),
                                'data' => $insert
                            ]
                        ]));
                    } else {
                        $insert = ITINVIncoming::updateOrInsert([
                            'BCTYPE' => $cekTempData['TYPE_BC'],
                            'BCDOCNO' => $noDaftar,
                            'BCDOCDT' => $cekTempData['TGL_DAFTAR'],
                            'ITMCD' => trim($row['kode_barang']),
                            // 'TTLQTY' => $row['jumlah_satuan'],
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

                        Redis::publish('portalv2', json_encode([
                            'app' => 'it_inv_checker',
                            'message' => $noDaftar . ' on date bc : ' . $cekTempData["TGL_DAFTAR"] . ' - INC Item Exists on updated',
                            'type' => 'info',
                            'status' => 'progress_bc_sync_item_inc_not_exists_wo_mega',
                            'data' => [
                                'NOMOR_DAFTAR' => $noDaftar,
                                'TGL_DAFTAR' => $cekTempData["TGL_DAFTAR"],
                                'updatedItem' => trim($row['kode_barang']),
                                'data' => $insert
                            ]
                        ]));
                    }
                } else {
                    // ITINVOutgoing::where('BCDOCNO', 'LIKE', $noDaftar . '%')
                    //     ->where('BCTYPE', $cekTempData['TYPE_BC'])
                    //     ->where('BCDOCDT', $cekTempData['TGL_DAFTAR'])
                    //     ->where('ITMCD', trim($row['kode_barang']))
                    //     ->where('TTLQTY', trim($row['jumlah_satuan']))
                    //     // ->havingRaw('SUM(TTLQTY) = ' . trim($row['jumlah_satuan']))
                    //     ->delete();

                    $UOM = 'PIECE';
                    if ($row['kode_satuan'] !== 'PCE') {
                        $UOM = $row['kode_satuan'];
                    }

                    $cekBCStatus = viewCeisaRespon::where('NOMOR_DAFTAR', $noDaftar)->where('TGL_DAFTAR', $cekTempData['TGL_DAFTAR'])->first();
                    $cekItemMega = DB::connection('sqlsrv_itinv')->table('VIEW_MITM_TBL')->where('MITM_ITMCD', $row['kode_barang'])->first();
                    if ($cekBCStatus->STAT_MEGABCDOC == 1 && !empty($cekItemMega)) {
                        $cekHeaderMega = DB::connection('sqlsrv_mega_db')
                            ->table('Z_STXI_VW_CBCDOC')
                            ->where('CBCDOC_BCDOCNO', $noDaftar)
                            ->where('CBCDOC_BCDOCDT', $cekTempData['TGL_DAFTAR'])
                            ->where('CBCDOCPRC_ITMCD', trim($row['kode_barang']))
                            ->first();

                        $insert = ITINVOutgoing::create(
                            [
                                'LOCCD' => empty($cekHeaderMega) ? 'STX-I' : (
                                    !empty($cekHeaderMega->FIFO_LOCCD)
                                    ? $cekHeaderMega->FIFO_LOCCD
                                    : $cekHeaderMega->CBCDOC_WHSCD
                                ),
                                'BCTYPE' => $cekTempData['TYPE_BC'],
                                'BCDOCNO' => $noDaftar,
                                'BCDOCDT' => $cekTempData['TGL_DAFTAR'],
                                'BSGRP' => trim($row['kode_barang']) == 'PALLET' || empty($cekHeaderMega) ? 'LAIN NYA' : (
                                    !empty($cekHeaderMega->FIFO_BSGRP)
                                    ? $cekHeaderMega->FIFO_BSGRP
                                    : $cekHeaderMega->CBCDOC_BSGRP
                                ),
                                'DOCCD' => $cekHeaderMega->CBCDOC_DOCCD,
                                'DOCNO' => $cekHeaderMega->CBCDOC_DOCNO,
                                'HHEINVNO' => '',
                                'ISUDT' => $cekHeaderMega->CBCDOC_ISUDT,
                                'ITMCD' => trim($row['kode_barang']),
                                'ITMD1' => $row['uraian'],
                                'SPTNO' => $row['tipe'],
                                'UOM' => $UOM,
                                'TTLQTY' => $row['jumlah_satuan'],
                                'CURCD' => $cekTempData['CURR'],
                                'PRICE' => round((int) ($cekTempData['TYPE_BC'] == 'P3BET' ? round((int) $row['nilai_devisa'], 4) : round((int) $row['cif'], 4)) / (int) $row['jumlah_satuan'], 4),
                                'TTLAMOUNT' => ($cekTempData['TYPE_BC'] == 'P3BET' ? round((int) $row['nilai_devisa'], 4) : round((int) $row['cif'], 4)),
                                'TAXINV' => '',
                                'CUSNM' => $cekTempData['PENERIMA'],
                                'WMSLOC' => '',
                                'HSCODE' => $row['hs']
                            ]
                        );

                        // $insert = ITINVOutgoing::updateOrCreate([
                        //     'BCTYPE' => $cekTempData['TYPE_BC'],
                        //     'BCDOCNO' => $noDaftar,
                        //     'BCDOCDT' => $cekTempData['TGL_DAFTAR'],
                        //     'ITMCD' => trim($row['kode_barang']),
                        //     // 'TTLQTY' => trim($row['jumlah_satuan'])
                        // ], [
                        //     'LOCCD' => empty($cekHeaderMega) ? 'STX-I' : (
                        //         !empty($cekHeaderMega->FIFO_LOCCD)
                        //         ? $cekHeaderMega->FIFO_LOCCD
                        //         : $cekHeaderMega->CBCDOC_WHSCD
                        //     ),
                        //     'BCTYPE' => $cekTempData['TYPE_BC'],
                        //     'BCDOCNO' => $noDaftar,
                        //     'BCDOCDT' => $cekTempData['TGL_DAFTAR'],
                        //     'BSGRP' => empty($cekHeaderMega) ? 'LAIN NYA' : (
                        //         !empty($cekHeaderMega->FIFO_BSGRP)
                        //         ? $cekHeaderMega->FIFO_BSGRP
                        //         : $cekHeaderMega->CBCDOC_BSGRP
                        //     ),
                        //     'DOCCD' => $cekHeaderMega->CBCDOC_DOCCD,
                        //     'DOCNO' => $cekHeaderMega->CBCDOC_DOCNO,
                        //     'HHEINVNO' => '',
                        //     'ISUDT' => $cekHeaderMega->CBCDOC_ISUDT,
                        //     'ITMCD' => trim($row['kode_barang']),
                        //     'ITMD1' => $row['uraian'],
                        //     'SPTNO' => $row['tipe'],
                        //     'UOM' => $UOM,
                        //     'TTLQTY' => $row['jumlah_satuan'],
                        //     'CURCD' => $cekTempData['CURR'],
                        //     'PRICE' => round((int) ($cekTempData['TYPE_BC'] == 'P3BET' ? round((int) $row['nilai_devisa'], 4) : round((int) $row['cif'], 4)) / (int) $row['jumlah_satuan'], 4),
                        //     'TTLAMOUNT' => ($cekTempData['TYPE_BC'] == 'P3BET' ? round((int) $row['nilai_devisa'], 4) : round((int) $row['cif'], 4)),
                        //     'TAXINV' => '',
                        //     'CUSNM' => $cekTempData['PENERIMA'],
                        //     'WMSLOC' => '',
                        //     'HSCODE' => $row['hs']
                        // ]);

                        Redis::publish('portalv2', json_encode([
                            'app' => 'it_inv_checker',
                            'message' => $noDaftar . ' on date bc : ' . $cekTempData["TGL_DAFTAR"] . ' - INC Item Exists on updated',
                            'type' => 'info',
                            'status' => 'progress_bc_sync_item_inc_not_exists_w_mega',
                            'data' => [
                                'NOMOR_DAFTAR' => $noDaftar,
                                'TGL_DAFTAR' => $cekTempData["TGL_DAFTAR"],
                                'updatedItem' => trim($row['kode_barang']),
                                'data' => $insert
                            ]
                        ]));
                    } else {
                        $cekOutgoingDataOnly = ITINVOutgoing::NoLock()->where('BCDOCNO', 'LIKE', $noDaftar . '%')
                            ->where('BCTYPE', $cekTempData['TYPE_BC'])
                            ->where('BCDOCDT', $cekTempData['TGL_DAFTAR'])
                            ->first();

                        $insert = ITINVOutgoing::updateOrInsert([
                            'BCTYPE' => $cekTempData['TYPE_BC'],
                            'BCDOCNO' => $noDaftar,
                            'BCDOCDT' => $cekTempData['TGL_DAFTAR'],
                            'ITMCD' => trim($row['kode_barang']),
                            // 'TTLQTY' => trim($row['jumlah_satuan'])
                        ], [
                            'LOCCD' => 'STX-I',
                            'BCTYPE' => $cekTempData['TYPE_BC'],
                            'BCDOCNO' => $noDaftar,
                            'BCDOCDT' => $cekTempData['TGL_DAFTAR'],
                            'BSGRP' => 'LAIN NYA',
                            'DOCCD' => '',
                            'DOCNO' => '',
                            'INVNO' => '',
                            'ISUDT' => empty($cekOutgoingDataOnly) ? $cekTempData['TGL_DAFTAR'] : $cekOutgoingDataOnly->ISUDT,
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

                        Redis::publish('portalv2', json_encode([
                            'app' => 'it_inv_checker',
                            'message' => $noDaftar . ' on date bc : ' . $cekTempData["TGL_DAFTAR"] . ' - INC Item Exists on updated',
                            'type' => 'info',
                            'status' => 'progress_bc_sync_item_out_not_exists',
                            'data' => [
                                'NOMOR_DAFTAR' => $noDaftar,
                                'TGL_DAFTAR' => $cekTempData["TGL_DAFTAR"],
                                'updatedItem' => trim($row['kode_barang']),
                                'data' => $insert
                            ]
                        ]));
                    }

                    // $cekOutgoing = ITINVOutgoing::NoLock()->where('BCDOCNO', 'LIKE', $noDaftar . '%')
                    //     ->where('BCTYPE', $cekTempData['TYPE_BC'])
                    //     ->where('BCDOCDT', $cekTempData['TGL_DAFTAR'])
                    //     ->where('ITMCD', trim($row['kode_barang']))
                    //     // ->where('TTLQTY', trim($row['jumlah_satuan']))
                    //     ->first();

                    // if (!empty($cekOutgoing)) {
                    //     ITINVOutgoing::where("BCDOCNO", 'LIKE', $noDaftar . '%')
                    //         ->where('BCTYPE', $cekTempData['TYPE_BC'])
                    //         ->where('BCDOCDT', $cekTempData["TGL_DAFTAR"])
                    //         ->where('ITMCD', trim($row['kode_barang']))
                    //         // ->where('TTLQTY', trim($row['jumlah_satuan']))
                    //         // ->havingRaw('SUM(TTLQTY) = ' . trim($row['jumlah_satuan']))
                    //         ->update([
                    //             'PRICE' => $row['cif'] == 0
                    //                 ? round((int) $row['harga_penyerahan'] / (int) $row['jumlah_satuan'], 4)
                    //                 : round((int) $row['cif'] / (int) $row['jumlah_satuan'], 4),
                    //             'TTLAMOUNT' => $row['cif'] == 0
                    //                 ? round((int) $row['harga_penyerahan'], 4)
                    //                 : round((int) $row['cif'], 4),
                    //             'CUSNM' => $cekTempData['PENERIMA'],
                    //             'ITMD1' => !empty($getHSCode) ? $getHSCode->MITM_ITMD1 : trim($row['uraian']),
                    //             'SPTNO' => !empty($getHSCode) ? $getHSCode->MITM_SPTNO : '',
                    //             'HSCODE' => $row['hs']
                    //         ]);

                    //     Redis::publish('portalv2', json_encode([
                    //         'app' => 'it_inv_checker',
                    //         'message' => $noDaftar . ' on date bc : ' . $cekTempData["TGL_DAFTAR"] . ' - OUT Item Exists on updated',
                    //         'type' => 'info',
                    //         'status' => 'progress_bc_sync_item_out_exists',
                    //         'data' => [
                    //             'NOMOR_DAFTAR' => $noDaftar,
                    //             'TGL_DAFTAR' => $cekTempData["TGL_DAFTAR"],
                    //             'updatedItem' => trim($row['kode_barang']),
                    //             'data' => $cekOutgoing
                    //         ]
                    //     ]));
                    // } else {
                    //     $UOM = 'PIECE';
                    //     if ($row['kode_satuan'] !== 'PCE') {
                    //         $UOM = $row['kode_satuan'];
                    //     }

                    //     $cekBCStatus = viewCeisaRespon::where('NOMOR_DAFTAR', $noDaftar)->where('TGL_DAFTAR', $cekTempData['TGL_DAFTAR'])->first();

                    //     if ($cekBCStatus->STAT_MEGABCDOC == 1) {
                    //         $cekHeaderMega = DB::connection('sqlsrv_mega_db')
                    //             ->table('Z_STXI_VW_CBCDOC')
                    //             ->where('CBCDOC_BCDOCNO', $noDaftar)
                    //             ->where('CBCDOC_BCTYPE', $cekTempData['TYPE_BC'])
                    //             ->first();

                    //         $insert = ITINVOutgoing::create(
                    //             [
                    //                 'LOCCD' => empty($cekHeaderMega) ? 'STX-I' : (
                    //                     !empty($cekHeaderMega->FIFO_LOCCD)
                    //                     ? $cekHeaderMega->FIFO_LOCCD
                    //                     : $cekHeaderMega->CBCDOC_WHSCD
                    //                 ),
                    //                 'BCTYPE' => $cekTempData['TYPE_BC'],
                    //                 'BCDOCNO' => $noDaftar,
                    //                 'BCDOCDT' => $cekTempData['TGL_DAFTAR'],
                    //                 'BSGRP' => empty($cekHeaderMega) ? 'LAIN NYA' : (
                    //                     !empty($cekHeaderMega->FIFO_BSGRP)
                    //                     ? $cekHeaderMega->FIFO_BSGRP
                    //                     : $cekHeaderMega->CBCDOC_BSGRP
                    //                 ),
                    //                 'DOCCD' => $cekHeaderMega->CBCDOC_DOCCD,
                    //                 'DOCNO' => $cekHeaderMega->CBCDOC_DOCNO,
                    //                 'HHEINVNO' => '',
                    //                 'ISUDT' => $cekHeaderMega->CBCDOC_ISUDT,
                    //                 'ITMCD' => trim($row['kode_barang']),
                    //                 'ITMD1' => $row['uraian'],
                    //                 'SPTNO' => $row['tipe'],
                    //                 'UOM' => $UOM,
                    //                 'TTLQTY' => $row['jumlah_satuan'],
                    //                 'CURCD' => $cekTempData['CURR'],
                    //                 'PRICE' => round((int) ($cekTempData['TYPE_BC'] == 'P3BET' ? round((int) $row['nilai_devisa'], 4) : round((int) $row['cif'], 4)) / (int) $row['jumlah_satuan'], 4),
                    //                 'TTLAMOUNT' => ($cekTempData['TYPE_BC'] == 'P3BET' ? round((int) $row['nilai_devisa'], 4) : round((int) $row['cif'], 4)),
                    //                 'TAXINV' => '',
                    //                 'CUSNM' => $cekTempData['PENERIMA'],
                    //                 'WMSLOC' => '',
                    //                 'HSCODE' => $row['hs']
                    //             ]
                    //         );

                    //         // $insert = ITINVOutgoing::updateOrCreate([
                    //         //     'BCTYPE' => $cekTempData['TYPE_BC'],
                    //         //     'BCDOCNO' => $noDaftar,
                    //         //     'BCDOCDT' => $cekTempData['TGL_DAFTAR'],
                    //         //     'ITMCD' => trim($row['kode_barang']),
                    //         //     // 'TTLQTY' => trim($row['jumlah_satuan'])
                    //         // ], [
                    //         //     'LOCCD' => empty($cekHeaderMega) ? 'STX-I' : (
                    //         //         !empty($cekHeaderMega->FIFO_LOCCD)
                    //         //         ? $cekHeaderMega->FIFO_LOCCD
                    //         //         : $cekHeaderMega->CBCDOC_WHSCD
                    //         //     ),
                    //         //     'BCTYPE' => $cekTempData['TYPE_BC'],
                    //         //     'BCDOCNO' => $noDaftar,
                    //         //     'BCDOCDT' => $cekTempData['TGL_DAFTAR'],
                    //         //     'BSGRP' => empty($cekHeaderMega) ? 'LAIN NYA' : (
                    //         //         !empty($cekHeaderMega->FIFO_BSGRP)
                    //         //         ? $cekHeaderMega->FIFO_BSGRP
                    //         //         : $cekHeaderMega->CBCDOC_BSGRP
                    //         //     ),
                    //         //     'DOCCD' => $cekHeaderMega->CBCDOC_DOCCD,
                    //         //     'DOCNO' => $cekHeaderMega->CBCDOC_DOCNO,
                    //         //     'HHEINVNO' => '',
                    //         //     'ISUDT' => $cekHeaderMega->CBCDOC_ISUDT,
                    //         //     'ITMCD' => trim($row['kode_barang']),
                    //         //     'ITMD1' => $row['uraian'],
                    //         //     'SPTNO' => $row['tipe'],
                    //         //     'UOM' => $UOM,
                    //         //     'TTLQTY' => $row['jumlah_satuan'],
                    //         //     'CURCD' => $cekTempData['CURR'],
                    //         //     'PRICE' => round((int) ($cekTempData['TYPE_BC'] == 'P3BET' ? round((int) $row['nilai_devisa'], 4) : round((int) $row['cif'], 4)) / (int) $row['jumlah_satuan'], 4),
                    //         //     'TTLAMOUNT' => ($cekTempData['TYPE_BC'] == 'P3BET' ? round((int) $row['nilai_devisa'], 4) : round((int) $row['cif'], 4)),
                    //         //     'TAXINV' => '',
                    //         //     'CUSNM' => $cekTempData['PENERIMA'],
                    //         //     'WMSLOC' => '',
                    //         //     'HSCODE' => $row['hs']
                    //         // ]);

                    //         Redis::publish('portalv2', json_encode([
                    //             'app' => 'it_inv_checker',
                    //             'message' => $noDaftar . ' on date bc : ' . $cekTempData["TGL_DAFTAR"] . ' - INC Item Exists on updated',
                    //             'type' => 'info',
                    //             'status' => 'progress_bc_sync_item_inc_not_exists_w_mega',
                    //             'data' => [
                    //                 'NOMOR_DAFTAR' => $noDaftar,
                    //                 'TGL_DAFTAR' => $cekTempData["TGL_DAFTAR"],
                    //                 'updatedItem' => trim($row['kode_barang']),
                    //                 'data' => $insert
                    //             ]
                    //         ]));
                    //     } else {
                    //         $cekOutgoingDataOnly = ITINVOutgoing::NoLock()->where('BCDOCNO', 'LIKE', $noDaftar . '%')
                    //             ->where('BCTYPE', $cekTempData['TYPE_BC'])
                    //             ->where('BCDOCDT', $cekTempData['TGL_DAFTAR'])
                    //             ->first();

                    //         $insert = ITINVOutgoing::updateOrCreate([
                    //             'BCTYPE' => $cekTempData['TYPE_BC'],
                    //             'BCDOCNO' => $noDaftar,
                    //             'BCDOCDT' => $cekTempData['TGL_DAFTAR'],
                    //             'ITMCD' => trim($row['kode_barang']),
                    //             // 'TTLQTY' => trim($row['jumlah_satuan'])
                    //         ], [
                    //             'LOCCD' => 'STX-I',
                    //             'BCTYPE' => $cekTempData['TYPE_BC'],
                    //             'BCDOCNO' => $noDaftar,
                    //             'BCDOCDT' => $cekTempData['TGL_DAFTAR'],
                    //             'BSGRP' => 'LAIN NYA',
                    //             'DOCCD' => '',
                    //             'DOCNO' => '',
                    //             'HHEINVNO' => '',
                    //             'ISUDT' => empty($cekOutgoingDataOnly) ? $cekTempData['TGL_DAFTAR'] : $cekOutgoingDataOnly->ISUDT,
                    //             'ITMCD' => trim($row['kode_barang']),
                    //             'ITMD1' => $row['uraian'],
                    //             'SPTNO' => $row['tipe'],
                    //             'UOM' => $UOM,
                    //             'TTLQTY' => $row['jumlah_satuan'],
                    //             'CURCD' => $cekTempData['CURR'],
                    //             'PRICE' => round((int) $row['cif'] / (int) $row['jumlah_satuan'], 4),
                    //             'TTLAMOUNT' => round((int) $row['cif'], 4),
                    //             'TAXINV' => '',
                    //             'CUSNM' => $cekTempData['PENERIMA'],
                    //             'WMSLOC' => '',
                    //             'HSCODE' => $row['hs']
                    //         ]);

                    //         Redis::publish('portalv2', json_encode([
                    //             'app' => 'it_inv_checker',
                    //             'message' => $noDaftar . ' on date bc : ' . $cekTempData["TGL_DAFTAR"] . ' - INC Item Exists on updated',
                    //             'type' => 'info',
                    //             'status' => 'progress_bc_sync_item_out_not_exists',
                    //             'data' => [
                    //                 'NOMOR_DAFTAR' => $noDaftar,
                    //                 'TGL_DAFTAR' => $cekTempData["TGL_DAFTAR"],
                    //                 'updatedItem' => trim($row['kode_barang']),
                    //                 'data' => $insert
                    //             ]
                    //         ]));
                    //     }
                    // }
                }
            }
        }

        logger(json_encode($row));
    }
}
