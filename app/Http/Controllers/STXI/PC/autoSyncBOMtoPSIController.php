<?php

namespace App\Http\Controllers\STXI\PC;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Jobs\STXI\PC\syncBOMToPSIQueue;
use App\Jobs\STXI\PC\syncDeletePSItoBOMQueue;

use App\Models\PSI\ENG\BOMSTX_TBL;
class autoSyncBOMtoPSIController extends Controller
{
    public function syncBOM(): string
    {
        $runTime = date('Y-m-d H:i:s');
        $getDataPA100 = DB::connection('sqlsrv_mega_sme')
            ->select('SET NOCOUNT ON;exec Z_STXI_DOWNLOAD_PA100_BOM_FOR_SYNC_PSI');

        $getDataPA100 = array_map(function ($valueDe2) {
            return (array) $valueDe2;
        }, $getDataPA100);
        
        foreach ($getDataPA100 as $key => $value) {
            syncBOMToPSIQueue::dispatch($value, $runTime)->onQueue('syncPA100BOMToPSI');
        }

        $getPSIDataForDelete = BOMSTX_TBL::where('APPROVED', 0)->get();
        foreach ($getPSIDataForDelete as $keyPSI => $valuePSI) {
            syncDeletePSItoBOMQueue::dispatch($getDataPA100, $valuePSI, $runTime)->onQueue('deletePA100BOMToPSI');
        }
        
        return 'Sync BOM Queued, Data to be updated : '.count($getDataPA100);
    }
}
