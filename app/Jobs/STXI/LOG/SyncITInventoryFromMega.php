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

class SyncITInventoryFromMega implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    private $date;
    public function __construct($date)
    {
        $this->date = $date;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
                    
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
    }
}
