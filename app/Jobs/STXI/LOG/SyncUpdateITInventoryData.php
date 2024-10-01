<?php

namespace App\Jobs\STXI\LOG;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\STXI\LOG\ITINVIncoming;
use App\Models\STXI\LOG\ITINVOutgoing;

class SyncUpdateITInventoryData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $insertedData, $incout;
    /**
     * Create a new job instance.
     */
    public function __construct($insertedData, $incout)
    {
        $this->insertedData = $insertedData;
        $this->incout = $incout;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->incout === 'INC') {
            ITINVIncoming::where('LOCCD', 'STX-I')
                ->where('DOCCD', 'LAIN NYA')
                ->where('ITMCD', $this->insertedData->ITMCD);
        } else {

        }
    }
}
