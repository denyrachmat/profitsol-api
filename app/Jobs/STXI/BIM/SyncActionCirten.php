<?php

namespace App\Jobs\STXI\BIM;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Excel;
use Redis;

use App\Imports\STXI\BIM\ImportCircularTen;
use App\Jobs\STXI\BIM\SyncCirTentoOldDMS;
class SyncActionCirten implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct($secTenNo, $epsTenNo, $HTMLPath, $excelPath)
    {
        $this->secTenNo = $secTenNo;
        $this->epsTenNo = $epsTenNo;
        $this->HTMLPath = $HTMLPath;
        $this->excelPath = $excelPath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $importer = new ImportCircularTen($this->secTenNo, $this->HTMLPath, $this->epsTenNo, 2, $this->excelPath);

        Redis::publish('portalv2', json_encode([
            'app' => 'cirten',
            'message' => 'TEN ' . $this->secTenNo . ' : Upload on progress !',
            'type' => 'green',
            'status' => 'info',
            'data' => $this
        ]));

        $cek = Excel::import($importer, $this->excelPath, 'ten_bim');
        // Send To DMS
        SyncCirTentoOldDMS::dispatch($importer->data['sendData'])->onQueue('SyncCirTentoOldDMS');
    }
}
