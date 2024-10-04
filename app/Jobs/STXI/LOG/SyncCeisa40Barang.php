<?php

namespace App\Jobs\STXI\LOG;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Traits\STXI\LOG\Ceisa40Traits;
class SyncCeisa40Barang implements ShouldQueue
{
    use Ceisa40Traits, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct($IDheader)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $getStatus = $this->apiPointData(
            "lnsw/get-data-doc/td-header?idHeader=",
            'GET',
            [],
            'parser',
            true
        );
    }
}
