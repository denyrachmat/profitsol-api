<?php

namespace App\Jobs\STXI\PC;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\STXI\PC\DOPackingList;

class autoMailDOPackingListQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public $data;
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to(array_values(array_filter($this->data['email'], function($f) {
            return $f['AMDC_EMAILTYPE'] == 'to';
        })))
            ->cc(array_values(array_filter($this->data['email'], function($f) {
                return $f['AMDC_EMAILTYPE'] == 'cc';
            })))
            ->send(new DOPackingList($this->data['data']));
    }
}
