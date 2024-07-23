<?php

namespace App\Jobs\STXI\LOG;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Traits\STXI\LOG\Ceisa40Traits;

use App\Models\STXI\CEISA40\CEISARESPON;
use App\Models\STXI\CEISA40\CR_STATUS_DET;

class SyncStatusBCFromCeisa implements ShouldQueue
{
    use Ceisa40Traits, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public $id;
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $getStatus = $this->apiPointData(
            "proses/getRiwayatStatus/{$this->id}",
            'GET',
            [],
            'parser',
            true
        );

        if (!empty($getStatus)) {
            foreach ($getStatus['data'] as $key => $value) {
                $getAJU = CEISARESPON::select('NOMOR_AJU', 'ID_HEADER')
                    ->where('ID_HEADER', $this->id)
                    ->groupBy('NOMOR_AJU', 'ID_HEADER')
                    ->first();

                CR_STATUS_DET::updateOrCreate([
                    'ID_HEADER' => $this->id,
                    'CRSD_NOMOR_AJU' => $getAJU->NOMOR_AJU,
                    'CRSD_RESNM' => $value['namaProses'],
                ], [
                    'ID_HEADER' => $this->id,
                    'CRSD_NOMOR_AJU' => $getAJU->NOMOR_AJU,
                    'CRSD_RESNM' => $value['namaProses'],
                    'CRSD_RESDTFR' => date('Y-m-d H:i:s', strtotime($value['waktuMulai'])),
                    'CRSD_RESDTTO' => date('Y-m-d H:i:s', strtotime($value['waktuSelesai'])),
                ]);
            }
        }
    }
}
