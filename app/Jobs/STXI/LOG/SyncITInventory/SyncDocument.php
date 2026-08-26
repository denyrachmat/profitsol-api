<?php

namespace App\Jobs\STXI\LOG\SyncITInventory;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\STXI\CEISA40\DOCUMENTCEISA;
use App\Models\STXI\CEISA40\HEADERCIESA;

use App\Models\STXI\LOG\ITINVIncoming;
use App\Models\STXI\LOG\ITINVOutgoing;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Redis;

class SyncDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $header;
    public $typeBC;
    public $dataTemp;
    public $dataBarang;
    /**
     * Create a new job instance.
     */
    public function __construct($header = [], $typeBC = 'INC', $dataTemp = [], $dataBarang = [])
    {
        $this->header = $header;
        $this->typeBC = $typeBC;
        $this->dataTemp = $dataTemp;
        $this->dataBarang = $dataBarang;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Sync data ke table document
            $getDataDocument = DOCUMENTCEISA::where('NOMOR AJU', $this->header['NOMOR AJU'])
                ->get()
                ->toArray();

            $dataBC23BCTYPE = '';
            $dataBC23DOCNO = '';
            $dataBC23DOCDT = '';
            $dataHHEInvNo = '';
            $dataTAXINV = '';
            $dataDocNo = '';
            $dataBC33Arr = [
                'BC33DOCNO' => '',
                'BC33DOCDT' => '',
                'BC33EXBCTYPE' => '',
                'BC33EXDOCNO' => '',
                'BC33EXDOCDT' => '',
            ];

            foreach ($getDataDocument as $key => $document) {
                if ($this->typeBC['type'] === 'INC') {
                    if ($document['KODE DOKUMEN'] == '380') {
                        $dataHHEInvNo = $key > 0 ? $dataHHEInvNo . ';' . $document['NOMOR DOKUMEN'] : $document['NOMOR DOKUMEN'];
                    } elseif ($document['KODE DOKUMEN'] == '388') {
                        $dataTAXINV = $key > 0 ? $dataTAXINV . ';' . $document['NOMOR DOKUMEN'] : $document['NOMOR DOKUMEN'];
                    } elseif ($document['KODE DOKUMEN'] == '640') {
                        $dataDocNo = $key > 0 ? $dataDocNo . ';' . $document['NOMOR DOKUMEN'] : $document['NOMOR DOKUMEN'];
                    }
                } else {
                    if ($document['KODE DOKUMEN'] == '380') {
                        $dataHHEInvNo = $key > 0 ? $dataHHEInvNo . ';' . $document['NOMOR DOKUMEN'] : $document['NOMOR DOKUMEN'];
                    } elseif ($document['KODE DOKUMEN'] == '640') {
                        $dataDocNo = $key > 0 ? $dataDocNo . ';' . $document['NOMOR DOKUMEN'] : $document['NOMOR DOKUMEN'];
                    }

                    // Get Ex-bc
                    if ($document['KODE DOKUMEN'] == '16') {
                        $dataBC23BCTYPE = 'BC1.6';
                        $dataBC23DOCNO = $document['NOMOR DOKUMEN'];
                        $dataBC23DOCDT = $document['TANGGAL DOKUMEN'];
                    } elseif ($document['KODE DOKUMEN'] == '33') {
                        $dataBC23BCTYPE = 'BC3.3';
                        $dataBC23DOCNO = $document['NOMOR DOKUMEN'];
                        $dataBC23DOCDT = $document['TANGGAL DOKUMEN'];

                        $dataBC33Arr['BC33DOCNO'] = $document['NOMOR DOKUMEN'];
                        $dataBC33Arr['BC33DOCDT'] = $document['TANGGAL DOKUMEN'];

                        $dataBC33 = HEADERCIESA::select(
                            '03_DOKUMEN.KODE DOKUMEN',
                            '03_DOKUMEN.NOMOR DOKUMEN',
                            '03_DOKUMEN.TANGGAL DOKUMEN'
                        )
                            ->where('01_HEADER.NOMOR AJU', $this->header['NOMOR AJU'])
                            ->where('01_HEADER.KODE DOKUMEN', '33')
                            ->join('03_DOKUMEN', '01_HEADER.NOMOR AJU', '=', '03_DOKUMEN.NOMOR AJU')
                            ->where('03_DOKUMEN.NOMOR DOKUMEN', $document['NOMOR DOKUMEN'])
                            ->get()
                            ->toArray();

                        foreach ($dataBC33 as $key => $documentEx33) {
                            if ($documentEx33['KODE DOKUMEN'] == '16' || $documentEx33['KODE DOKUMEN'] == '40') {
                                $dataBC33Arr['BC33EXBCTYPE'] = $documentEx33['KODE DOKUMEN'] == '16' ? 'BC1.6' : 'BC4.0';
                                $dataBC33Arr['BC33EXDOCNO'] = $documentEx33['NOMOR DOKUMEN'];
                                $dataBC33Arr['BC33EXDOCDT'] = $documentEx33['TANGGAL DOKUMEN'];
                            }
                        }
                    }
                }
            }

            if ($this->typeBC['type'] === 'INC') {
                $dataInc = ITINVIncoming::where(DB::raw('LEFT(BCDOCNO, 6)'), substr($this->header['NOMOR DAFTAR'], 0, 6))
                    ->where('BCTYPE', $this->typeBC['code'])
                    ->where('BCDOCDT', $this->header['TANGGAL DAFTAR']);


                $dataInc->whereNull(DB::raw("NULLIF(HHEINVNO, '')"))
                    ->update([
                        'HHEINVNO' => $dataHHEInvNo,
                    ]);

                $dataInc->whereNull(DB::raw("NULLIF(TAXINV, '')"))
                    ->update([
                        'TAXINV' => $dataTAXINV,
                    ]);

                $dataInc->whereNull(DB::raw("NULLIF(BCDOCNO, '')"))
                    ->update([
                        'BCDOCNO' => $dataDocNo,
                    ]);
            } else {
                $dataOut = ITINVOutgoing::where(DB::raw('LEFT(BCDOCNO, 6)'), substr($this->header['NOMOR DAFTAR'], 0, 6))
                    ->where('BCTYPE', $this->typeBC['code'])
                    ->where('BCDOCDT', $this->header['TANGGAL DAFTAR']);

                $dataOut->whereNull(DB::raw("NULLIF(HHEINVNO, '')"))
                    ->update([
                        'TAXINV' => $dataHHEInvNo,
                    ]);

                $dataOut->whereNull(DB::raw("NULLIF(BCDOCNO, '')"))
                    ->update([
                        'BCDOCNO' => $dataDocNo,
                    ]);

                $dataOut->whereNull(DB::raw("NULLIF(BC23BCTYPE, '')"))
                    ->update([
                        'BC23BCTYPE' => $dataBC23BCTYPE,
                        'BC23DOCNO' => $dataBC23DOCNO,
                        'BC23DOCDT' => $dataBC23DOCDT,
                    ]);

                $dataOut->whereNull(DB::raw("NULLIF(BC33DOCNO, '')"))
                    ->update([
                        'BC33DOCNO' => $dataBC33Arr['BC33DOCNO'],
                        'BC33DOCDT' => $dataBC33Arr['BC33DOCDT'],
                        'BC33EXBCTYPE' => $dataBC33Arr['BC33EXBCTYPE'],
                        'BC33EXDOCNO' => $dataBC33Arr['BC33EXDOCNO'],
                        'BC33EXDOCDT' => $dataBC33Arr['BC33EXDOCDT'],
                    ]);
            }

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
                            'data' => $this->header,
                            'is_failed' => false,
                        ],
                        'entitas' => [
                            'status' => true,
                            'data' => [
                                'PENGIRIM' => !empty($this->dataTemp['PENGIRIM']) ? $this->dataTemp['PENGIRIM'] : '',
                                'SUPPL' => !empty($this->dataTemp['SUPPL']) ? $this->dataTemp['SUPPL'] : '',
                                'PENERIMA' => !empty($this->dataTemp['PENERIMA']) ? $this->dataTemp['PENERIMA'] : '',
                            ],
                            'is_failed' => false,
                        ],
                        'barang' => [
                            'status' => true,
                            'data' => $this->dataBarang,
                            'is_failed' => false,
                        ],
                        'document' => [
                            'status' => true,
                            'data' => $getDataDocument,
                            'is_failed' => false,
                        ],
                    ]
                ],
            ));
        } catch (\Exception $e) {
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
                            'data' => $this->header,
                            'is_failed' => false,
                        ],
                        'entitas' => [
                            'status' => true,
                            'data' => [
                                'PENGIRIM' => !empty($this->dataTemp['PENGIRIM']) ? $this->dataTemp['PENGIRIM'] : '',
                                'SUPPL' => !empty($this->dataTemp['SUPPL']) ? $this->dataTemp['SUPPL'] : '',
                                'PENERIMA' => !empty($this->dataTemp['PENERIMA']) ? $this->dataTemp['PENERIMA'] : '',
                            ],
                            'is_failed' => false,
                        ],
                        'barang' => [
                            'status' => true,
                            'data' => $this->dataBarang,
                            'is_failed' => false,
                        ],
                        'document' => [
                            'status' => true,
                            'data' => $getDataDocument,
                            'is_failed' => true,
                            'failed_message' => $e->getMessage(),
                        ],
                    ]
                ],
            ));
        }
    }
}
