<?php

namespace App\Jobs\STXI\PC;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\PSI\ENG\BOMSTX_TBL;
use App\Models\STXI\PC\BPSM_MSTR;

class syncDeletePSItoBOMQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $dataPA100, $dataBOM, $runTime;
    /**
     * Create a new job instance.
     */
    public function __construct($dataPA100, $dataBOM, $runTime)
    {
        $this->dataPA100 = $dataPA100;
        $this->dataBOM = $dataBOM;
        $this->runTime = $runTime;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $searchData = array_values(array_filter($this->dataPA100, function ($f) use ($this) {
            return $f['MODEL CODE'] == $this->dataBOM['MODEL_CODE'] &&
                $f['REVISION'] == $this->dataBOM['REVISION'] &&
                $f['MAIN PART CODE'] == $this->dataBOM['MAIN_PART_CODE'];
        }));

        if (count($searchData) === 0) {
            BOMSTX_TBL::where('MODEL_CODE', $this->dataBOM['MODEL_CODE'])
                ->where('REVISION', $this->dataBOM['REVISION'])
                ->where('MAIN_PART_CODE', $this->dataBOM['MAIN_PART_CODE'])
                ->where('APPROVED', 0)
                ->delete();

            BPSM_MSTR::updateOrCreate([
                'BPSM_ITMCD' => $this->dataBOM['MAIN_PART_CODE'],
                'BPSM_MDLCD' => $this->dataBOM['MODEL_CODE'],
                'BPSM_REV' => $this->dataBOM['REVISION'],
            ], [
                'BPSM_ITMCD' => $this->dataBOM['MAIN_PART_CODE'],
                'BPSM_MDLCD' => $this->dataBOM['MODEL_CODE'],
                'BPSM_REV' => $this->dataBOM['REVISION'],
                'BPSM_STAT' => 3,
                'BPSM_REMARKS' => 'BOM Was deleted !',
                'BPSM_RUNTIME' => $this->runTime,
            ]);
        }
    }
}
