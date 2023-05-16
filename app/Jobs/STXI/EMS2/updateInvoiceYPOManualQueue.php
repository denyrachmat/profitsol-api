<?php

namespace App\Jobs\STXI\EMS2;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\STXI\EMS2\YPOSTXIPODet;
use Illuminate\Support\Facades\DB;

class updateInvoiceYPOManualQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // protected $po, $item;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->po = $po;
        // $this->item = $item;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = YPOSTXIPODet::join('YPO_MSTR_TBL', 'YMT_ID', 'YPO_MSTR_TBL.id')->whereNull('YSPDT_INVNO')->get();

        if (count($data) > 0) {
            foreach ($data as $key => $value) {    
                $dataView = DB::connection('sqlsrv_ems2')->table('V_YPO_OS_GIT')
                    ->where('PPO1_PONO', $value->YSPDT_PONO)
                    ->where('PPO2_ITMCD', $value->YPO_ITMCD)
                    ->where('PGIT_RCVQT', '>=', $value->YSPDT_POQTY)
                    ->orderBy('PPO1_ISUDT', 'asc')
                    ->first();
                
                if (!empty($dataView)) {
                    YPOSTXIPODet::where('YMT_ID', $value->YMT_ID)
                        ->where('YSPDT_PONO', $value->YSPDT_PONO)
                        ->update([
                            'YSPDT_INVNO' => $dataView->PGIT_SUPNO,
                            'YSPDT_POQT' => (int)$dataView->PGIT_RCVQT
                        ]);
                }
            }
        }
    }
}
