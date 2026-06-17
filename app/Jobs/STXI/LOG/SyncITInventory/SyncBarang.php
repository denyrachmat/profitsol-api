<?php

namespace App\Jobs\STXI\LOG\SyncITInventory;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\STXI\CEISA40\BARANGCEISA;
use App\Models\STXI\LOG\ITINVIncoming;
use App\Models\STXI\LOG\ITINVOutgoing;
use Illuminate\Support\Facades\DB;

use App\Jobs\STXI\LOG\SyncITInventory\SyncDocument;
use Illuminate\Support\Facades\Redis;
class SyncBarang implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $header;
    public $typeBC;
    public $dataTemp;
    /**
     * Create a new job instance.
     */
    public function __construct($header = [], $typeBC = 'INC', $dataTemp = [])
    {
        $this->header = $header;
        $this->typeBC = $typeBC;
        $this->dataTemp = $dataTemp;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $checkBCDocOnMega = DB::connection('sqlsrv_mega_db')
                ->table('Z_STXI_VW_CBCDOC')
                ->where('CBCDOC_BCDOCNO', 'like', $this->header['NOMOR DAFTAR'] . '%')
                ->where('CBCDOC_BCDOCDT', $this->header['TANGGAL DAFTAR'])
                ->get();

            $checkBCDocOnMega = json_decode(json_encode($checkBCDocOnMega), true);

            // Jika data di Mega tersedia, maka gunakan data tersebut, jika tidak maka ambil dari database 
            if (count($checkBCDocOnMega) > 0) {
                $getfirstDataDoc = $checkBCDocOnMega[0] ?? null;

                // Insert data barang dari Mega ke database ITINVIncoming atau ITINVOutgoing
                foreach ($checkBCDocOnMega as $key => $barang) {
                    $this->sendNotification($checkBCDocOnMega, $barang, 'Found data on Mega, processing data.', false, $key + 1);
                    $barang = (array) $barang;

                    $cekItemMega = DB::connection('sqlsrv_itinv')->table('VIEW_MITM_TBL')->where('MITM_ITMCD', $barang['CBCDOCPRC_ITMCD'])->first();
                    $getDataItem = json_decode(json_encode($cekItemMega), true);

                    $wmsLoc = DB::connection('sqlsrv_mega_db')
                        ->table('Z_STXI_TBL_WMSLOC')
                        ->where('ITMCD', trim($barang['CBCDOCPRC_ITMCD']))
                        ->first();

                    if ($this->typeBC['type'] === 'INC') {
                        $processedBarang[] = [
                            'LOCCD' => !empty($barang['FIFO_LOCCD'])
                                ? $barang['FIFO_LOCCD']
                                : $barang['CBCDOC_WHSCD'],
                            'BCTYPE' => $this->typeBC['code'],
                            'BCDOCNO' => $this->header['NOMOR DAFTAR'],
                            'BCDOCDT' => $this->header['TANGGAL DAFTAR'],
                            'BSGRP' => !empty($barang['FIFO_BSGRP'])
                                ? $barang['FIFO_BSGRP']
                                : $barang['CBCDOC_BSGRP'],
                            'DOCCD' => $barang['CBCDOC_DOCCD'],
                            'DOCNO' => $barang['CBCDOC_DOCNO'],
                            'HHEINVNO' => $barang['PGITSHP_SHPREFNO'] ?? '',
                            'ISUDT' => $barang['CBCDOC_ISUDT'],
                            'ITMCD' => trim($barang['CBCDOCPRC_ITMCD']),
                            'ITMD1' => $getDataItem ? $getDataItem['MITM_ITMD1'] : '',
                            'SPTNO' => $getDataItem ? $getDataItem['MITM_SPTNO'] : '',
                            'UOM' => $getDataItem ? $getDataItem['MITM_STKUOM'] : '',
                            'TTLQTY' => round((int) $barang['CBCDOCPRC_QTY'], 4),
                            'CURCD' => $barang['CBCDOC_CURCD'],
                            'PRICE' => round((float) $barang['CBCDOCPRC_CPRICE'], 4),
                            'TTLAMOUNT' => round((int) $barang['CBCDOCPRC_QTY'] * (float) $barang['CBCDOCPRC_CPRICE'], 4),
                            'TAXINV' => $this->typeBC['code'] == 'BC4.0' ? $barang['PGITSHP_SHPREFNO'] ?? '' : '',
                            'SUPNM' => $this->dataTemp['SUPPL'],
                            'PENGIRIM' => $this->dataTemp['PENGIRIM'],
                            'WMSLOC' => $wmsLoc ? $wmsLoc->WMSLOC : '',
                            'HSCODE' => $getDataItem ? $getDataItem['MITM_HSCD'] : '',
                            'LUPDT' => now(),
                        ];
                    } else {
                        $processedBarang[] = [
                            'LOCCD' => !empty($barang['FIFO_LOCCD'])
                                ? $barang['FIFO_LOCCD']
                                : $barang['CBCDOC_WHSCD'],
                            'BCTYPE' => $this->typeBC['code'],
                            'BCDOCNO' => $this->header['NOMOR DAFTAR'],
                            'BCDOCDT' => $this->header['TGL_DAFTAR'],
                            'BSGRP' => !empty($barang['FIFO_BSGRP'])
                                ? $barang['FIFO_BSGRP']
                                : $barang['CBCDOC_BSGRP'],
                            'DOCCD' => $barang['CBCDOC_DOCCD'],
                            'DOCNO' => $barang['CBCDOC_DOCNO'],
                            'HHEINVNO' => '',
                            'ISUDT' => $barang['CBCDOC_ISUDT'],
                            'ITMCD' => trim($barang['CBCDOCPRC_ITMCD']),
                            'ITMD1' => $getDataItem ? $getDataItem['MITM_ITMD1'] : '',
                            'SPTNO' => $getDataItem ? $getDataItem['MITM_SPTNO'] : '',
                            'UOM' => $getDataItem ? $getDataItem['MITM_STKUOM'] : '',
                            'TTLQTY' => round((int) $barang['CBCDOCPRC_QTY'], 4),
                            'CURCD' => $barang['CBCDOC_CURCD'],
                            'PRICE' => round((float) $barang['CBCDOCPRC_CPRICE'], 4),
                            'TTLAMOUNT' => round((int) $barang['CBCDOCPRC_QTY'] * (float) $barang['CBCDOCPRC_CPRICE'], 4),
                            'TAXINV' => '',
                            'CUSNM' => $this->header['PENERIMA'],
                            'WMSLOC' => $wmsLoc ? $wmsLoc->WMSLOC : '',
                            'HSCODE' => $getDataItem ? $getDataItem['MITM_HSCD'] : '',
                            'LUPDT' => now(),
                            'BC33DOCNO' => '',
                            'BC33DOCDT' => '',
                            'BC33EXBCTYPE' => '',
                            'BC33EXBCDOCNO' => '',
                            'BC33EXBCDOCDT' => '',
                            'BC23BCTYPE' => '',
                        ];
                    }
                }

                // Insert data barang diluar Mega ke database ITINVIncoming atau ITINVOutgoing
                $getBarang = BARANGCEISA::where('NOMOR AJU', $this->header['NOMOR AJU'])
                    ->get()
                    ->toArray();

                $listBarangOnMega = array_map(function ($item) {
                    return trim($item['ITMCD']);
                }, $processedBarang);

                $listBarangCeisaNotOnMega = array_filter($getBarang, function ($item) use ($listBarangOnMega) {
                    return !in_array(trim($item['KODE BARANG']), $listBarangOnMega);
                });

                foreach ($listBarangCeisaNotOnMega as $key => $barangNotOnMega) {
                    $this->sendNotification($listBarangCeisaNotOnMega, $barangNotOnMega, 'Found data on Mega but not included, processing data.', false, $key + 1);
                    $barangNotOnMega['KODE SATUAN'] = $barangNotOnMega['KODE SATUAN'] !== 'PCE' ? $barangNotOnMega['KODE SATUAN'] : 'PIECE';
                    $listDocNo = array_map(function ($item) {
                        return trim($item['DOCNO']);
                    }, $processedBarang);
                    $listHHEInvNo = array_map(function ($item) {
                        return trim($item['HHEINVNO']);
                    }, $processedBarang);

                    $wmsLoc = DB::connection('sqlsrv_mega_db')
                        ->table('Z_STXI_TBL_WMSLOC')
                        ->where('ITMCD', trim($barangNotOnMega['KODE BARANG']))
                        ->first();

                    if ($this->typeBC['type'] === 'INC') {
                        $processedBarang[] = [
                            'LOCCD' => $processedBarang[0]['LOCCD'] ?? 'STX-I',
                            'BCTYPE' => $this->typeBC['code'],
                            'BCDOCNO' => $this->header['NOMOR DAFTAR'],
                            'BCDOCDT' => $this->header['TANGGAL DAFTAR'],
                            'BSGRP' => $processedBarang[0]['LOCCD'] ?? 'LAIN NYA',
                            'DOCCD' => $processedBarang[0]['DOCCD'] ?? '',
                            'DOCNO' => implode(';', $listDocNo),
                            'HHEINVNO' => implode(';', $listHHEInvNo),
                            'ISUDT' => $this->header['TANGGAL DAFTAR'],
                            'ITMCD' => trim($barangNotOnMega['KODE BARANG']),
                            'ITMD1' => $barangNotOnMega['URAIAN'],
                            'SPTNO' => $barangNotOnMega['TIPE'],
                            'UOM' => $barangNotOnMega['KODE SATUAN'],
                            'TTLQTY' => $barangNotOnMega['JUMLAH SATUAN'],
                            'CURCD' => $this->header['KODE VALUTA'],
                            'PRICE' => $barangNotOnMega['HARGA PENYERAHAN'] === 0
                                ? round((float) $barangNotOnMega['CIF'] / (int) $barangNotOnMega['JUMLAH SATUAN'], 4)
                                : round((float) $barangNotOnMega['HARGA PENYERAHAN'] / (int) $barangNotOnMega['JUMLAH SATUAN'], 4),
                            'TTLAMOUNT' => round((float) $barangNotOnMega['HARGA PENYERAHAN'], 4) === 0
                                ? round((float) $barangNotOnMega['CIF'], 4)
                                : round((float) $barangNotOnMega['HARGA PENYERAHAN'], 4),
                            'TAXINV' => '',
                            'SUPNM' => $this->dataTemp['SUPPL'] ?? null,
                            'PENGIRIM' => $this->dataTemp['PENGIRIM'] ?? null,
                            'WMSLOC' => $wmsLoc ? $wmsLoc->WMSLOC : '',
                            'HSCODE' => $barangNotOnMega['HS']
                        ];
                    } else {
                        $processedBarang[] = [
                            'LOCCD' => $processedBarang[0]['LOCCD'] ?? 'STX-I',
                            'BCTYPE' => $this->typeBC['code'],
                            'BCDOCNO' => $this->header['NOMOR DAFTAR'],
                            'BCDOCDT' => $this->header['TANGGAL DAFTAR'],
                            'BSGRP' => $processedBarang[0]['LOCCD'] ?? 'LAIN NYA',
                            'DOCCD' => $processedBarang[0]['DOCCD'] ?? '',
                            'DOCNO' => implode(';', $listDocNo),
                            'HHEINVNO' => implode(';', $listHHEInvNo),
                            'ISUDT' => $this->header['TANGGAL DAFTAR'],
                            'ITMCD' => trim($barangNotOnMega['KODE BARANG']),
                            'ITMD1' => $barangNotOnMega['URAIAN'],
                            'SPTNO' => $barangNotOnMega['TIPE'],
                            'UOM' => $barangNotOnMega['KODE SATUAN'],
                            'TTLQTY' => $barangNotOnMega['JUMLAH SATUAN'],
                            'CURCD' => $this->header['KODE VALUTA'],
                            'PRICE' => $barangNotOnMega['HARGA PENYERAHAN'] === 0
                                ? round((float) $barangNotOnMega['CIF'] / (int) $barangNotOnMega['JUMLAH SATUAN'], 4)
                                : round((float) $barangNotOnMega['HARGA PENYERAHAN'] / (int) $barangNotOnMega['JUMLAH SATUAN'], 4),
                            'TTLAMOUNT' => round((float) $barangNotOnMega['HARGA PENYERAHAN'], 4) === 0
                                ? round((float) $barangNotOnMega['CIF'], 4)
                                : round((float) $barangNotOnMega['HARGA PENYERAHAN'], 4),
                            'TAXINV' => '',
                            'CUSNM' => $this->header['PENERIMA'],
                            'WMSLOC' => '',
                            'HSCODE' => $barangNotOnMega['HS'],
                            'LUPDT' => now(),
                            'BC33DOCNO' => '',
                            'BC33DOCDT' => '',
                            'BC33EXBCTYPE' => '',
                            'BC33EXBCDOCNO' => '',
                            'BC33EXBCDOCDT' => '',
                            'BC23BCTYPE' => ''
                        ];
                    }
                }
            } else {
                $getBarang = BARANGCEISA::where('NOMOR AJU', $this->header['NOMOR AJU'])
                    ->get()
                    ->toArray();
                $processedBarang = [];
                foreach ($getBarang as $key => $barang) {
                    $wmsLoc = DB::connection('sqlsrv_mega_db')
                        ->table('Z_STXI_TBL_WMSLOC')
                        ->where('ITMCD', trim($barang['KODE BARANG']))
                        ->first();
                    $this->sendNotification($getBarang, $barang, 'Cannot found data on mega, use Ceisa Export processing, processing data.', false, $key + 1);
                    if ($this->typeBC['type'] === 'INC') {
                        $barang['KODE SATUAN'] = $barang['KODE SATUAN'] !== 'PCE' ? $barang['KODE SATUAN'] : 'PIECE';

                        $processedBarang[] = [
                            'LOCCD' => 'STX-I',
                            'BCTYPE' => $this->typeBC['code'],
                            'BCDOCNO' => $this->header['NOMOR DAFTAR'],
                            'BCDOCDT' => $this->header['TANGGAL DAFTAR'],
                            'BSGRP' => 'LAIN NYA',
                            'DOCCD' => '',
                            'DOCNO' => '',
                            'HHEINVNO' => '',
                            'ISUDT' => $this->header['TANGGAL DAFTAR'],
                            'ITMCD' => trim($barang['KODE BARANG']),
                            'ITMD1' => $barang['URAIAN'],
                            'SPTNO' => $barang['TIPE'],
                            'UOM' => $barang['KODE SATUAN'],
                            'TTLQTY' => $barang['JUMLAH SATUAN'],
                            'CURCD' => $this->header['KODE VALUTA'],
                            'PRICE' => $barang['HARGA PENYERAHAN'] === 0
                                ? round((float) $barang['CIF'] / (int) $barang['JUMLAH SATUAN'], 4)
                                : round((float) $barang['HARGA PENYERAHAN'] / (int) $barang['JUMLAH SATUAN'], 4),
                            'TTLAMOUNT' => round((float) $barang['HARGA PENYERAHAN'], 4) === 0
                                ? round((float) $barang['CIF'], 4)
                                : round((float) $barang['HARGA PENYERAHAN'], 4),
                            'TAXINV' => '',
                            'SUPNM' => $this->dataTemp['SUPPL'] ?? null,
                            'PENGIRIM' => $this->dataTemp['PENGIRIM'] ?? null,
                            'WMSLOC' => $wmsLoc ? $wmsLoc->WMSLOC : '',
                            'HSCODE' => $barang['HS']
                        ];
                    } else {
                        $processedBarang[] = [
                            'LOCCD' => 'STX-I',
                            'BCTYPE' => $this->typeBC['code'],
                            'BCDOCNO' => $this->header['NOMOR DAFTAR'],
                            'BCDOCDT' => $this->header['TGL_DAFTAR'],
                            'BSGRP' => 'LAIN NYA',
                            'DOCCD' => '',
                            'DOCNO' => '',
                            'HHEINVNO' => '',
                            'ISUDT' => $this->header['TANGGAL DAFTAR'],
                            'ITMCD' => trim($barang['KODE BARANG']),
                            'ITMD1' => $barang['URAIAN'],
                            'SPTNO' => $barang['TIPE'],
                            'UOM' => $barang['KODE SATUAN'],
                            'TTLQTY' => $barang['JUMLAH SATUAN'],
                            'CURCD' => $this->header['KODE VALUTA'],
                            'PRICE' => $barang['HARGA PENYERAHAN'] === 0
                                ? round((float) $barang['CIF'] / (int) $barang['JUMLAH SATUAN'], 4)
                                : round((float) $barang['HARGA PENYERAHAN'] / (int) $barang['JUMLAH SATUAN'], 4),
                            'TTLAMOUNT' => round((float) $barang['HARGA PENYERAHAN'], 4) === 0
                                ? round((float) $barang['CIF'], 4)
                                : round((float) $barang['HARGA PENYERAHAN'], 4),
                            'TAXINV' => '',
                            'CUSNM' => $this->header['PENERIMA'],
                            'WMSLOC' => $wmsLoc ? $wmsLoc->WMSLOC : '',
                            'HSCODE' => $barang['HS'],
                            'LUPDT' => now(),
                            'BC33DOCNO' => '',
                            'BC33DOCDT' => '',
                            'BC33EXBCTYPE' => '',
                            'BC33EXBCDOCNO' => '',
                            'BC33EXBCDOCDT' => '',
                            'BC23BCTYPE' => ''
                        ];
                    }
                }
            }

            // logger('processedBarang : ' . json_encode($processedBarang));
            if ($this->typeBC['type'] === 'INC') {
                $chunkSize = 50;

                // Pecah array besar menjadi array-array kecil
                $dataChunks = array_chunk($processedBarang, $chunkSize);

                // Insert ke database per potongan di dalam loop
                foreach ($dataChunks as $chunk) {
                    ITINVIncoming::insert($chunk);
                }
            } else {
                $chunkSize = 50;

                // Pecah array besar menjadi array-array kecil
                $dataChunks = array_chunk($processedBarang, $chunkSize);

                $dataChunks = array_chunk($processedBarang, $chunkSize);
                foreach ($dataChunks as $chunk) {
                    ITINVOutgoing::insert($chunk);
                }
            }

            $this->sendNotification($processedBarang, $processedBarang[0], 'Cannot found data on mega, use Ceisa Export processing, processing data.', true, count($processedBarang), true);

            // Sync data ke table document
            SyncDocument::dispatch($this->header, $this->typeBC, $this->dataTemp, [
                'status' => true,
                'total' => count($processedBarang),
                'processed' => count($processedBarang),
                'current' => null,
            ])->onQueue('sync-itinventory');
        } catch (\Exception $e) {
            $this->sendNotification([], null, 'Failed to synchronize data Barang', true, [], true);
        }

    }

    public function sendNotification($listDataBarang, $barang, $status, $statusFlag = false, $processed = 0, $isfailed = false)
    {
        Redis::publish('portalv2', json_encode(
            [
                'app' => 'it_inv_ceisa_upload',
                'status' => 'start',
                'message' => 'List bc no will be synchronized !',
                'type' => 'info',
                'key' => $this->header['NOMOR AJU'],
                'data' => [
                    'header' => [
                        'status' => true,
                        'is_failed' => false,
                        'data' => $this->header,
                    ],
                    'entitas' => [
                        'status' => true,
                        'is_failed' => false,
                        'data' => [
                            'PENGIRIM' => $this->dataTemp['PENGIRIM'],
                            'SUPPL' => $this->dataTemp['SUPPL'],
                            'PENERIMA' => $this->dataTemp['PENERIMA'],
                        ],
                    ],
                    'barang' => [
                        'status' => $statusFlag,
                        'is_failed' => $isfailed,
                        'data' => [
                            'status' => $status,
                            'total' => count($listDataBarang),
                            'processed' => $processed,
                            'current' => !$statusFlag ? $barang : null,
                        ],
                    ],
                    'document' => [
                        'status' => false,
                        'is_failed' => false,
                        'data' => [],
                    ],
                ]
            ],
        ));
    }
}
