<?php

namespace App\Jobs\STXI\LOG;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Excel;

use App\Traits\STXI\LOG\Ceisa40Traits;
use App\Models\STXI\CEISA40\viewCeisaRespon;
use App\Imports\STXI\LOG\ImportCeisa40;

class SyncITInventoryQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Ceisa40Traits;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Update un-sync data in this month first 
        $dataUnsync = viewCeisaRespon::whereBetween('TGL_DAFTAR', [date('Y-m-01'), date('Y-m-t')])
            ->whereNull('TYPE_DOC')
            ->orderBy('TGL_DAFTAR', 'DESC')
            ->get();

        foreach ($dataUnsync as $key => $value) {
            $downloadExcel = $this->downloadExcel($value->NOMOR_AJU, $value->CEISA_TYPE, $value->ID_HEADER, false);
            
            if (str_contains($downloadExcel, '1.6') || str_contains($downloadExcel, '2.7I') || str_contains($downloadExcel, '4.0')) {
                logger('ini incoming !!');
                $state = 'INC';
            } else {
                $state = 'OUT';
            }
    
            $importer = new ImportCeisa40($state);
    
            Excel::import($importer, public_path($downloadExcel));
        }
    }
}
