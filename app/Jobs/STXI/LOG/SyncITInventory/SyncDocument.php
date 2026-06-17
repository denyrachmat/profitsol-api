<?php

namespace App\Jobs\STXI\LOG\SyncITInventory;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\STXI\CEISA40\DOCUMENTCEISA;
use App\Models\STXI\LOG\ITINVIncoming;
use App\Models\STXI\LOG\ITINVOutgoing;

use Illuminate\Support\Facades\DB;

class SyncDocument implements ShouldQueue
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
                    'HHEINVNO' => $dataHHEInvNo,
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
        }
    }
}
