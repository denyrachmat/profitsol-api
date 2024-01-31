<?php

namespace App\Jobs\STXI\LOG;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Excel;
use Illuminate\Support\Facades\DB;

use App\Traits\STXI\LOG\Ceisa40Traits;
use App\Models\STXI\CEISA40\viewCeisaRespon;
use App\Imports\STXI\LOG\ImportCeisa40;
use Maatwebsite\Excel\Concerns\ToArray;
use Redis;

use App\Jobs\STXI\LOG\SyncITInventoryByBCNo;
use App\Jobs\STXI\LOG\SyncITInventoryFromMega;

class SyncITInventoryQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Ceisa40Traits;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $fdate, $ldate, $isSyncMega, $isIfaceCeisa;
    public function __construct($fdate = '', $ldate = '', $isSyncMega = false, $isIfaceCeisa = true)
    {
        $this->fdate = $fdate;
        $this->ldate = $ldate;
        $this->isSyncMega = $isSyncMega;
        $this->isIfaceCeisa = $isIfaceCeisa;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        set_time_limit(17600);
        // $redis = Redis::connection();
        
        // Update un-sync data in this month first 
        $dataUnsync = viewCeisaRespon::whereBetween('TGL_DAFTAR', [$this->fdate ? $this->fdate : date('Y-m-01'), $this->ldate ? $this->ldate : date('Y-m-t')])
            ->orderBy('TGL_DAFTAR', 'DESC')
            ->get();

        $begin = new \DateTime($this->fdate);
        $end = new \DateTime(date('Y-m-d H:i:s', strtotime($this->ldate . ' +1 day')));
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($begin, $interval, $end);

        // return $this->handleResponse($period, 'Sync data queued !!');
        $sync = [];

        if ($this->fdate !== $this->ldate) {
            foreach ($period as $key => $value) {
                $sync[] = $value->format("Y-m-d");
            }
        } else {
            $sync[] = $this->fdate;
        }

        Redis::publish('portalv2', json_encode([
            'app' => 'it_inv_checker',
            'status' => 'start',
            'message' => 'List bc no will be synchronized !',
            'type' => 'info',
            'data' => $dataUnsync 
        ]));

        if ($this->isSyncMega) {
            foreach ($period as $keyDate => $valueDate) {
                SyncITInventoryFromMega::dispatch($valueDate->format("Y-m-d"), $this->isSyncMega, $this->isIfaceCeisa)->onQueue('SyncITInventoryFromMega');
            }
        }

        Redis::publish('portalv2', json_encode([
            'app' => 'it_inv_checker',
            'message' => 'sync from portal ceisa 40 data will be start.',
            'type' => 'info',
            'data' => $dataUnsync
        ]));
    }
}
