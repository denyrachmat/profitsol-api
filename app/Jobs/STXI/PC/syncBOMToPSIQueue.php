<?php

namespace App\Jobs\STXI\PC;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\PSI\ENG\BOMSTX_TBL;
use App\Models\STXI\PC\BPSM_MSTR;

class syncBOMToPSIQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data, $runTime;
    /**
     * Create a new job instance.
     */
    public function __construct($data, $runTime)
    {
        $this->data = $data;
        $this->runTime = $runTime;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $bomSTXonPSI = BOMSTX_TBL::NoLock()->updateOrCreate([
            'MODEL_CODE' => $this->data['MODEL CODE'],
            'REVISION' => $this->data['REVISION'],
            'MAIN_PART_CODE' => $this->data['MAIN PART CODE'],
            'MAIN_SPTNO' => $this->data['MAIN SPTNO'],
            'MS_NO' => $this->data['MS NO'],
            // 'TEN_UPDATE_DATE' => $this->data['TEN_UPDATE_DATE'],
            'IEI_TEN_NO' => trim($this->data['IEI TEN NO']) == '' ? 'N/A' : trim($this->data['IEI TEN NO']),
            'PART_QTY' => $this->data['PART QTY'],
        ], [
            'MODEL_CODE' => $this->data['MODEL CODE'],
            'MODEL_DESC' => $this->data['MODEL DESC'],
            'REVISION' => $this->data['REVISION'],
            'MAIN_PART_CODE' => $this->data['MAIN PART CODE'],
            'MAIN_SPTNO' => $this->data['MAIN SPTNO'],
            'MAIN_MAKERNM' => $this->data['MAIN MAKERNM'],
            'MS_NO' => $this->data['MS NO'],
            'MODEL_QTY' => $this->data['MODEL QTY'],
            'PART_QTY' => $this->data['PART QTY'],
            'MAIN_PA_PERCENT' => $this->data['MAIN PA%'],
            'PO_FAILURE' => $this->data['PO FAILURE'],
            'KO_FAILURE' => $this->data['KO FAILURE'],
            'DETAIL_REMARK' => $this->data['DETAIL REMARK'],
            'CONSIDER_PO_MRP' => $this->data['CONSIDER PO MRP'],
            'CONSIDER_KO_MRP' => $this->data['CONSIDER KO MRP'],
            'PROCESS_CODE' => $this->data['PROCESS CODE'],
            'EPSON_ORG_PART' => $this->data['EPSON ORG PART'],
            'EPSON_SPTNO' => $this->data['EPSON SPTNO'],
            'EPSON_MAKERNM' => $this->data['EPSON MAKERNM'],
            'BOM_REMARK' => $this->data['BOM REMARK'],
            'SUB' => $this->data['SUB'],
            'SUB_SPTNO' => $this->data['SUB SPTNO'],
            'SUB_MAKERNM' => $this->data['SUB MAKERNM'],
            'SUB_PA_PERCENT' => $this->data['SUB PA%'],
            'SUB1' => $this->data['SUB1'],
            'SUB1_SPTNO' => $this->data['SUB1 SPTNO'],
            'SUB2' => $this->data[''],
            'SUB2_SPTNO' => $this->data['SUB2 SPTNO'],
            'IEI_TEN_NO' => trim($this->data['IEI TEN NO']) == '' ? 'N/A' : trim($this->data['IEI TEN NO']),
            'SEC_TEN_NO' => $this->data['SEC TEN NO'],
            'TEN_RECEIVE_DATE' => $this->data['TEN RECEIVE DATE'],
            'CHANGE_OVERVIEW' => $this->data['CHANGE OVERVIEW'],
            // 'STOCK_SGL' => 0,
            // 'STOCK_CPO' => 0,
            'TEN_UPDATE_DATE' => $this->data['TEN_UPDATE_DATE'],
            'UPDDT' => $this->runTime,
        ]);

        if(!$bomSTXonPSI->wasRecentlyCreated && $bomSTXonPSI->wasChanged()){
            // updateOrCreate performed an update
            BPSM_MSTR::updateOrCreate([
                'BPSM_ITMCD' => $this->data['MAIN PART CODE'],
                'BPSM_MDLCD' => $this->data['MODEL CODE'],
                'BPSM_REV' => $this->data['REVISION'],
            ],[
                'BPSM_ITMCD' => $this->data['MAIN PART CODE'],
                'BPSM_MDLCD' => $this->data['MODEL CODE'],
                'BPSM_REV' => $this->data['REVISION'],
                'BPSM_STAT' => 2,
                'BPSM_REMARKS' => 'BOM Was updated !',
                'BPSM_RUNTIME' => $this->runTime,
            ]);
        }
        
        // if(!$bomSTXonPSI->wasRecentlyCreated && !$bomSTXonPSI->wasChanged()){
        //     // updateOrCreate performed nothing, row did not change
            
        // }
        
        if($bomSTXonPSI->wasRecentlyCreated){
            BPSM_MSTR::updateOrCreate([
                'BPSM_ITMCD' => $this->data['MAIN PART CODE'],
                'BPSM_MDLCD' => $this->data['MODEL CODE'],
                'BPSM_REV' => $this->data['REVISION'],
            ],[
                'BPSM_ITMCD' => $this->data['MAIN PART CODE'],
                'BPSM_MDLCD' => $this->data['MODEL CODE'],
                'BPSM_REV' => $this->data['REVISION'],
                'BPSM_STAT' => 1,
                'BPSM_REMARKS' => 'BOM Was inserted !',
                'BPSM_RUNTIME' => $this->runTime,
            ]);

           // updateOrCreate performed create
        }
    }
}
