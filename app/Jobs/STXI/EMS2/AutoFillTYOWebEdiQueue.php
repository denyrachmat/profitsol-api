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

    /**
     * Create a new job instance.
     */
    public function __construct($data, $url, $deliveryDate, $id)
    {
        $this->url = $url;
        $this->data = $data;
        $this->deliveryDate = $deliveryDate;
        $this->id = $id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Redis::publish('portalv2', json_encode([
            'app' => 'auto_fill_tyo_webedi',
            'message' => 'ID ' . $this->id . ', PO ('.$this->data[1].') : Input to TYO WebEDI on progress !',
            'type' => 'green',
            'status' => 'start',
            'data' => $this->data
        ]));

        $process = Process::path('D:\app\stx-i-automation\robot-tyo-barcode-creator')
            ->run('C:\Python311\python.exe -m robocorp.tasks run tasks.py -- --data "' . $this->url . '"');

        TYOA_BC_MSTR::updateorcreate([
            'TYOAM_PONO' => $this->data[1],
            'TYOAM_DLVDT' => $this->deliveryDate,
        ], [
            'TYOA_ID' => $this->id,
            'TYOAM_PONO' => $this->data[1],
            'TYOAM_ITMCD' => $this->data[0],
            'TYOAM_QTY' => $this->data[2],
            'TYOAM_JOBNO' => $this->data[6],
            'TYOAM_DLVDT' => $this->deliveryDate,
            'TYOAM_STAT' => $process->successful(),
            'TYOAM_REMARKS' => $process->errorOutput()
        ]);

        Redis::publish('portalv2', json_encode([
            'app' => 'auto_fill_tyo_webedi',
            'message' => 'ID ' . $this->id . ', PO ('.$this->data[1].') : '. $process->successful() ? 'Successfully inputed to WEBEdi': 'Failed input to WEBEdi',
            'type' => $process->successful() ? 'green' : 'red',
            'status' => 'end',
            'data' => $this->data
        ]));
    }
}
