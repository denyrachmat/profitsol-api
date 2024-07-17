<?php

namespace App\Jobs\STXI\PC;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Mail\STXI\PC\DOPackingList;
use Illuminate\Support\Facades\Mail;

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
        $listTo = $listCC = [];
        foreach ($this->data['email'] as $key => $value) {
            if ($value['AMDC_EMAILTYPE'] == 'to') {
                $listTo[] = $value['AMDC_EMAIL'];
            }

            if ($value['AMDC_EMAILTYPE'] == 'cc') {
                $listCC[] = $value['AMDC_EMAIL'];
            }
        }
        Mail::to($listTo)
            ->cc($listCC)
            ->send(new DOPackingList($this->data['data']));
    }
}
