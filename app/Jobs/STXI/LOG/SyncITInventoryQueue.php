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

class SyncITInventoryQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Ceisa40Traits;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $fdate, $ldate;
    public function __construct($fdate = '', $ldate = '')
    {
        $this->fdate = $fdate;
        $this->ldate = $ldate;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // $redis = Redis::connection();
        
        // Update un-sync data in this month first 
        $dataUnsync = viewCeisaRespon::whereBetween('TGL_DAFTAR', [$this->fdate ? $this->fdate : date('Y-m-01'), $this->ldate ? $this->ldate : date('Y-m-t')])
            // ->whereNull('TYPE_DOC')
            // ->where('STAT_MEGABCDOC', 1)
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

        $cekMEGAUnsync = array_filter((clone $dataUnsync)->ToArray(), function($f){
            return $f['STAT_MEGABCDOC'] == 0;
        }); 

        if (count($cekMEGAUnsync) > 0) {
            foreach ($sync as $keyDate => $valueDate) {            
                Redis::publish('portalv2', json_encode([
                    'app' => 'it_inv_checker',
                    'message' => $valueDate. ' data not sync !, start sync now...',
                    'type' => 'info'
                ]));

                DB::connection('sqlsrv_itinv')->select("exec IF_CR_ALL_BYDAY('".$valueDate."', 1)");

                Redis::publish('portalv2', json_encode([
                    'app' => 'it_inv_checker',
                    'message' => $valueDate. ' data sync !! please check on IT Inventory',
                    'type' => 'success'
                ]));
            }
        }

        Redis::publish('portalv2', json_encode([
            'app' => 'it_inv_checker',
            'message' => $this->fdate. ' - '. $this->ldate .' sync data start',
            'type' => 'info',
            'data' => (clone $dataUnsync)->toArray()
        ]));

        $commRedis = [];
        foreach ($dataUnsync as $key => $value) {

            Redis::publish('portalv2', json_encode([
                'app' => 'it_inv_checker',
                'message' => $value->NOMOR_DAFTAR. ' - sync from portal ceisa 40 data now...',
                'type' => 'info'
            ]));
            
            $downloadExcel = $this->downloadExcel($value->NOMOR_AJU, $value->CEISA_TYPE, $value->ID_HEADER, false);
            
            if (str_contains($downloadExcel, '1.6') || str_contains($downloadExcel, '2.7I') || str_contains($downloadExcel, '4.0')) {
                $state = 'INC';
            } else {
                $state = 'OUT';
            }
    
            $importer = new ImportCeisa40($state);
    
            Excel::import($importer, public_path($downloadExcel));

            Redis::publish('portalv2', json_encode([
                'app' => 'it_inv_checker',
                'message' => $value->TGL_DAFTAR. ' sync, portal ceisa 40 done !',
                'type' => 'success'
            ]));
        }
    }
}
