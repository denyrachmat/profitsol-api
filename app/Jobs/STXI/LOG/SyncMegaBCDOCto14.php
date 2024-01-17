<?php

namespace App\Jobs\STXI\LOG;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

use App\Models\STXI\LOG\BCMega;

class SyncMegaBCDOCto14 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $cekStatBCMega = DB::connection('sqlsrv_itinv')->table('VEW_BCDOC')->get();

        foreach ($cekStatBCMega as $key => $value) {
            BCMega::updateOrCreate([
                'BCMG_BCDOCNO' => $value->CBCDOC_BCDOCNO,
                'BCMG_BCDOCDT' => $value->CBCDOC_BCDOCDT,
            ],[
                'BCMG_TYPE' => $value->CBCDOC_BCTYPE,
                'BCMG_BCDOCNO' => $value->CBCDOC_BCDOCNO,
                'BCMG_BCDOCDT' => $value->CBCDOC_BCDOCDT,
            ]);
        }
    }
}
