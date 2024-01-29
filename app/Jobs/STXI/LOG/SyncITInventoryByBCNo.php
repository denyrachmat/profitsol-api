<?php

namespace App\Jobs\STXI\LOG;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Redis;
use Excel;

use App\Models\STXI\CEISA40\viewCeisaRespon;

use App\Traits\STXI\LOG\Ceisa40Traits;
use App\Imports\STXI\LOG\ImportCeisa40;
class SyncITInventoryByBCNo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Ceisa40Traits;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $nodaftar, $tgldaftar;
    public function __construct($nodaftar, $tgldaftar)
    {
        $this->nodaftar = $nodaftar;
        $this->tgldaftar = $tgldaftar;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        set_time_limit(3600);
        $dataUnsync = viewCeisaRespon::where('TGL_DAFTAR', $this->tgldaftar)
            ->where('NOMOR_DAFTAR', $this->nodaftar)
            ->orderBy('TGL_DAFTAR', 'DESC')
            ->first();

        if (!empty($dataUnsync)) {
            Redis::publish('portalv2', json_encode([
                'app' => 'it_inv_checker',
                'message' => $this->nodaftar. ' on date bc : '. $this->tgldaftar .' - sync from portal ceisa 40 data now...',
                'type' => 'info',
                'status' => 'start_bc_sync',
                'data' => [
                    'nodaftar' => $this->nodaftar,
                    'tgldaftar' => $this->tgldaftar
                ]
            ]));
            
            $downloadExcel = $this->downloadExcel($dataUnsync->NOMOR_AJU, $dataUnsync->CEISA_TYPE, $dataUnsync->ID_HEADER, false);
            
            if (str_contains($downloadExcel, '1.6') || str_contains($downloadExcel, '2.7I') || str_contains($downloadExcel, '4.0')) {
                $state = 'INC';
            } else {
                $state = 'OUT';
            }
    
            $importer = new ImportCeisa40($state);
    
            Excel::import($importer, public_path($downloadExcel));
    
            Redis::publish('portalv2', json_encode([
                'app' => 'it_inv_checker',
                'message' => $this->nodaftar. ' on date bc : '. $this->tgldaftar .' sync from portal ceisa 40 done !',
                'type' => 'green',
                'status' => 'success_bc_sync',
                'data' => [
                    'nodaftar' => $this->nodaftar,
                    'tgldaftar' => $this->tgldaftar
                ]
            ]));
        } else {
            Redis::publish('portalv2', json_encode([
                'app' => 'it_inv_checker',
                'message' => $this->nodaftar. ' on date bc : '. $this->tgldaftar .' sync failed, data not found on ceisa 40 !',
                'type' => 'red',
                'status' => 'failed_bc_sync',
                'data' => [
                    'nodaftar' => $this->nodaftar,
                    'tgldaftar' => $this->tgldaftar
                ]
            ]));
        }
    }
}
