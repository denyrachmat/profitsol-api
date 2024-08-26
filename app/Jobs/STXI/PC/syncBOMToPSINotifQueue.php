<?php

namespace App\Jobs\STXI\PC;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

use App\Mail\STXI\PC\BOMSyncToPSI;
class syncBOMToPSINotifQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public $data, $to, $cc;
    public function __construct($data, $to, $cc)
    {
        $this->data = $data;
        $this->to = $to;
        $this->cc = $cc;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->to)
            ->cc($this->cc)
            ->send(new BOMSyncToPSI($this->data));
    }
}
