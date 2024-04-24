<?php

namespace App\Jobs\STXI\EMS2;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Redis;

use App\Models\STXI\EMS2\TYOA_BC_MSTR;

class AutoFillTYOWebEdiQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data, $url, $deliveryDate, $ids;
    /**
     * Create a new job instance.
     */
    public function __construct($data, $url, $deliveryDate, $ids)
    {
        $this->url = $url;
        $this->data = $data;
        $this->deliveryDate = $deliveryDate;
        $this->ids = $ids;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        
        set_time_limit(3600);
        try {
            Redis::publish('portalv2', json_encode([
                'app' => 'auto_fill_tyo_webedi',
                'message' => 'ID ' . $this->ids . ', PO ('.$this->data[1].') : Input to TYO WebEDI on progress !',
                'type' => 'green',
                'status' => 'start',
                'data' => $this->data
            ]));
    
            TYOA_BC_MSTR::updateorcreate([
                'TYOAM_PONO' => $this->data[1],
                'TYOAM_DLVDT' => $this->deliveryDate,
            ], [
                'TYOA_ID' => $this->ids,
                'TYOAM_PONO' => $this->data[1],
                'TYOAM_ITMCD' => $this->data[0],
                'TYOAM_QTY' => $this->data[2],
                'TYOAM_JOBNO' => $this->data[6],
                'TYOAM_DLVDT' => $this->deliveryDate,
                'TYOAM_STAT' => 2,
                'TYOAM_REMARKS' => 'On Progress Data.'
            ]);
    
            $process = Process::path('D:\app\stx-i-automation\robot-tyo-barcode-creator')
                ->run('C:\Python311\python.exe -m robocorp.tasks run tasks.py -- --data "' . $this->url . '"');
            
            if($process->successful()) {
                TYOA_BC_MSTR::updateorcreate([
                    'TYOAM_PONO' => $this->data[1],
                    'TYOAM_DLVDT' => $this->deliveryDate,
                ], [
                    'TYOAM_STAT' => $process->successful(),
                    'TYOAM_REMARKS' => iconv('','UTF-8',$process->errorOutput())
                ]);
        
                Redis::publish('portalv2', json_encode([
                    'app' => 'auto_fill_tyo_webedi',
                    'message' => 'ID ' . $this->ids . ', PO ('.$this->data[1].') : Successfully inputed to WEBEdi',
                    'type' => 'green',
                    'status' => 'end',
                    'data' => $this->data
                ]));
            } else {
                TYOA_BC_MSTR::updateorcreate([
                    'TYOAM_PONO' => $this->data[1],
                    'TYOAM_DLVDT' => $this->deliveryDate,
                ], [
                    'TYOAM_STAT' => $process->successful(),
                    'TYOAM_REMARKS' => iconv('','UTF-8',$process->errorOutput())
                ]);
        
                Redis::publish('portalv2', json_encode([
                    'app' => 'auto_fill_tyo_webedi',
                    'message' => 'ID ' . $this->ids . ', PO ('.$this->data[1].') : Failed input to WEBEdi',
                    'type' => 'red',
                    'status' => 'end',
                    'data' => $this->data
                ]));
            }
        } catch (\Throwable $th) {
            TYOA_BC_MSTR::updateorcreate([
                'TYOAM_PONO' => $this->data[1],
                'TYOAM_DLVDT' => $this->deliveryDate,
            ], [
                'TYOAM_STAT' => 0,
                'TYOAM_REMARKS' => $th->getMessage()
            ]);

            Redis::publish('portalv2', json_encode([
                'app' => 'auto_fill_tyo_webedi',
                'message' => 'ID ' . $this->ids . ', PO ('.$this->data[1].') : '.$th->getMessage(),
                'type' => 'red',
                'data' => $th->getMessage(),
                'status' => 'failed',
            ]));
        }
    }
}
