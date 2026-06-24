<?php

namespace App\Jobs\STXI\LOG\SyncITInventory;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\STXI\CEISA40\HEADERCIESA;
use App\Models\STXI\LOG\ITINVIncoming;
use App\Models\STXI\LOG\ITINVOutgoing;
use App\Models\STXI\LOG\ITINVUploadTemp;
use App\Models\STXI\CEISA40\CEISARESPON;

use Illuminate\Support\Facades\DB;


use App\Jobs\STXI\LOG\SyncITInventory\SyncEntitas;
use App\Jobs\STXI\LOG\SyncITInventory\SyncBarang;

use Illuminate\Support\Facades\Redis;

class SyncHeader implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $selectedData, $mode;

    /**
     * Create a new job instance.
     */
    public function __construct($selectedData = [], $mode = 'auto')
    {
        $this->selectedData = $selectedData;
        $this->mode = $mode;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            foreach ($this->selectedData as $header) {
                Redis::publish('portalv2', json_encode(
                    [
                        'app' => 'it_inv_ceisa_upload',
                        'status' => 'start',
                        'message' => 'List bc no will be synchronized !',
                        'type' => 'info',
                        'key' => $header['NOMOR AJU'],
                        'data' => [
                            'header' => [
                                'status' => false,
                                'data' => $header,
                            ],
                            'entitas' => [
                                'status' => false,
                                'data' => [],
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

                $kodeDokumen = $this->mapDocumentCode($header['KODE DOKUMEN']);

                if ($kodeDokumen['type'] === 'INC') {
                    ITINVIncoming::where("BCDOCNO", 'LIKE', $header['NOMOR DAFTAR'] . '%')
                        ->where('BCTYPE', $kodeDokumen['code'])
                        ->where('BCDOCDT', $header["TANGGAL DAFTAR"])
                        ->delete();
                } else {
                    ITINVOutgoing::where("BCDOCNO", 'LIKE', $header['NOMOR DAFTAR'] . '%')
                        ->where('BCTYPE', $kodeDokumen['code'])
                        ->where('BCDOCDT', $header["TANGGAL DAFTAR"])
                        ->delete();
                }

                $dataTemp = ITINVUploadTemp::updateOrCreate([
                    'NO_AJU' => $header['NOMOR AJU'],
                    'NO_DAFTAR' => $header['NOMOR DAFTAR'],
                    'TYPE_BC' => $kodeDokumen['code'],
                    'STATE_FLG' => $kodeDokumen['type']
                ], [
                    'NO_AJU' => $header['NOMOR AJU'],
                    'NO_DAFTAR' => $header['NOMOR DAFTAR'],
                    'TGL_DAFTAR' => $header['TANGGAL DAFTAR'],
                    'TYPE_BC' => $kodeDokumen['code'],
                    'CURR' => !empty($header['KODE VALUTA']) ? $header['KODE VALUTA'] : (
                        $header['KODE DOKUMEN'] == 40
                        ? 'IDR'
                        : 'USD'
                    ),
                    'STATE_FLG' => $kodeDokumen['type']
                ]);

                Redis::publish('portalv2', json_encode(
                    [
                        'app' => 'it_inv_ceisa_upload',
                        'status' => 'start',
                        'message' => 'List bc no will be synchronized !',
                        'type' => 'info',
                        'key' => $header['NOMOR AJU'],
                        'data' => [
                            'header' => [
                                'status' => true,
                                'data' => $header,
                                'is_failed' => false,
                            ],
                            'entitas' => [
                                'status' => false,
                                'data' => [],
                                'is_failed' => false,
                            ],
                            'barang' => [
                                'status' => false,
                                'data' => [],
                                'is_failed' => false,
                            ],
                            'document' => [
                                'status' => false,
                                'data' => [],
                                'is_failed' => false,
                            ],
                        ]
                    ],
                ));

                SyncEntitas::dispatch($header, $kodeDokumen, $dataTemp, $this->mode)->onQueue('sync-itinventory');
            }
        } catch (\Exception $e) {
            Redis::publish('portalv2', json_encode(
                [
                    'app' => 'it_inv_ceisa_upload',
                    'status' => 'start',
                    'message' => 'List bc no will be synchronized !',
                    'type' => 'info',
                    'key' => $header['NOMOR AJU'],
                    'data' => [
                        'header' => [
                            'status' => true,
                            'data' => [],
                            'is_failed' => true,
                        ],
                        'entitas' => [
                            'status' => false,
                            'data' => [],
                            'is_failed' => false,
                        ],
                        'barang' => [
                            'status' => false,
                            'data' => [],
                            'is_failed' => false,
                        ],
                        'document' => [
                            'status' => false,
                            'data' => [],
                            'is_failed' => false,
                        ],
                    ]
                ],
            ));
        }
    }

    public function mapDocumentCode($nomorDokumen)
    {
        $mapping = [
            '16' => ['type' => 'INC', 'code' => 'BC1.6'],  // Pemasukan barang dari luar pabean ke TPB
            '20' => ['type' => 'INC', 'code' => 'BC2.0'],  // Impor untuk Dipakai (Pemasukan)
            '23' => ['type' => 'INC', 'code' => 'BC2.3'],  // Pemasukan barang ke Kawasan Berikat
            '25' => ['type' => 'INC', 'code' => 'BC2.5'],  // Pengeluaran dari Kawasan Berikat ke DPIL (Fungsi masuk ke lokal)
            '27I' => ['type' => 'INC', 'code' => 'BC2.7I'],  // Pemasukan dari Kawasan Berikat lain
            '28' => ['type' => 'INC', 'code' => 'BC2.8'],  // Pemasukan ke Gudang Berikat
            '40' => ['type' => 'INC', 'code' => 'BC4.0'],  // Pemasukan dari DPIL ke Kawasan Berikat
            '331' => ['type' => 'OUT', 'code' => 'P3BET'],  // Pemberitahuan Pemasukan/Pengeluaran Barang Eks Teroris/Tertentu
            '27' => ['type' => 'OUT', 'code' => 'BC2.7'],  // Pengeluaran dari Kawasan Berikat ke KB lainnya
            '30' => ['type' => 'OUT', 'code' => 'BC3.0'],  // Ekspor (Pengeluaran) -> DIUBAH ke OUT
            '33' => ['type' => 'OUT', 'code' => 'BC3.3'],  // Pengeluaran dari KB ke PLB / TLBB -> DIUBAH ke OUT
            '41' => ['type' => 'OUT', 'code' => 'BC4.1'],  // Pengeluaran dari Kawasan Berikat untuk subkontrak (Keluar sementara) -> DIUBAH ke OUT
        ];

        return $mapping[$nomorDokumen] ?? null; // Atau nilai default jika tidak ada kecocokan
    }
}
