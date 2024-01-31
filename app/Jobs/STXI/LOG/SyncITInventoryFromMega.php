<?php

namespace App\Jobs\STXI\LOG;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Redis;
use Illuminate\Support\Facades\DB;

use App\Models\STXI\CEISA40\viewCeisaRespon;

class SyncITInventoryFromMega implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    private $date, $isIfaceMega, $isIfaceCeisa;
    public function __construct($date, $isIfaceMega = true, $isIfaceCeisa = false)
    {
        $this->date = $date;
        $this->isIfaceMega = $isIfaceMega;
        $this->isIfaceCeisa = $isIfaceCeisa;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->isIfaceMega) {
            Redis::publish('portalv2', json_encode([
                'app' => 'it_inv_checker',
                'message' => $this->date. ' start mega sync to it inventory now...',
                'type' => 'info',
                'status' => 'start_mega_resync',
                'data' => $this->date 
            ]));
    
            DB::connection('sqlsrv_itinv')->update("SET NOCOUNT ON;EXEC IF_CR_ALL_BYDAY @IFDT_Str='".$this->date."', @SUMFLG=1");
    
            Redis::publish('portalv2', json_encode([
                'app' => 'it_inv_checker',
                'message' => $this->date. ' data sync !! please check on IT Inventory',
                'type' => 'green',
                'status' => 'success_mega_resync',
                'data' => $this->date 
            ]));
        } else {
            Redis::publish('portalv2', json_encode([
                'app' => 'it_inv_checker',
                'message' => 'Mega sync skipping date : '.$this->date,
                'type' => 'orange',
                'data' => $this->date 
            ]));
        }

        if ($this->isIfaceCeisa) {
            Redis::publish('portalv2', json_encode([
                'app' => 'it_inv_checker',
                'message' => 'date : '.$this->date . ' sync data from ceisa 4.0',
                'type' => 'info',
                'data' => $this->date 
            ]));

            $dataUnsync = viewCeisaRespon::where('TGL_DAFTAR', $this->date)
            ->orderBy('TGL_DAFTAR', 'DESC')
            ->get();
    
            foreach ($dataUnsync as $key => $valueData) {
                SyncITInventoryByBCNo::dispatch($valueData->NOMOR_DAFTAR, $valueData->TGL_DAFTAR)->onQueue('SyncITInventoryByBCNo');
            }
        } else {
            Redis::publish('portalv2', json_encode([
                'app' => 'it_inv_checker',
                'message' => 'date : '.$this->date . ' sync data from ceisa 4.0 is skipped',
                'type' => 'orange',
                'data' => $this->date 
            ]));
        }
    }
}
