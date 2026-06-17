<?php

namespace App\Jobs\STXI\LOG\SyncITInventory;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\STXI\CEISA40\ENTITASCEISA;
use App\Models\STXI\LOG\ITINVUploadTemp;

use App\Jobs\STXI\LOG\SyncITInventory\SyncBarang;
use Illuminate\Support\Facades\Redis;

class SyncEntitas implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $header, $typeBC, $dataTemp;
    /**
     * Create a new job instance.
     */
    public function __construct($header = [], $typeBC = [], $dataTemp = [])
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
        $getEntitas = ENTITASCEISA::where('NOMOR AJU', $this->header['NOMOR AJU'])
            ->get()
            ->toArray();
        $pengirim = '';
        $supplier = '';
        $penerima = '';
        foreach ($getEntitas as $key => $entitas) {
            if ($this->typeBC['type'] === 'INC') {
                if (in_array($entitas['KODE ENTITAS'], ['9'])) {
                    $pengirim = $entitas['NAMA ENTITAS'];
                } elseif (in_array($entitas['KODE ENTITAS'], ['10'])) {
                    $supplier = $entitas['NAMA ENTITAS'];
                } elseif (in_array($entitas['KODE ENTITAS'], ['8'])) {
                    $penerima = $entitas['NAMA ENTITAS'];
                }
            } else {
                if (in_array($entitas['KODE ENTITAS'], ['7'])) {
                    $pengirim = $entitas['NAMA ENTITAS'];
                } elseif (in_array($entitas['KODE ENTITAS'], ['8'])) {
                    $penerima = $entitas['NAMA ENTITAS'];
                }
            }
        }

        ITINVUploadTemp::updateOrCreate([
            'NO_AJU' => $this->header['NOMOR AJU'],
            'NO_DAFTAR' => $this->header['NOMOR DAFTAR']
        ], [
            'NO_AJU' => $this->header['NOMOR AJU'],
            'NO_DAFTAR' => $this->header['NOMOR DAFTAR'],
            'PENGIRIM' => $pengirim,
            'SUPPL' => $supplier,
            'PENERIMA' => $penerima,
        ]);

        Redis::publish('portalv2', json_encode(
            [
                'app' => 'it_inv_ceisa_upload',
                'status' => 'start',
                'message' => 'List bc no will be synchronized !',
                'type' => 'info',
                'data' => [
                    'header' => [
                        'status' => true,
                        'data' => $this->header,
                    ],
                    'entitas' => [
                        'status' => true,
                        'data' => [
                            'PENGIRIM' => $pengirim,
                            'SUPPL' => $supplier,
                            'PENERIMA' => $penerima,
                        ],
                    ],
                    'barang' => [
                        'status' => false,
                        'data' => [],
                    ],
                    'document' => [
                        'status' => false,
                        'data' => [],
                    ],
                ]
            ],
        ));

        SyncBarang::dispatch($this->header, $this->typeBC, $this->dataTemp)->onQueue('sync-itinventory');
    }
}
