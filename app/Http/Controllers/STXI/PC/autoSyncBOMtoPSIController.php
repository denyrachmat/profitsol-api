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
    public function syncBOM(): array
    {
        $runTime = date('Y-m-d H:i:s');
        $getDataPA100 = DB::connection('sqlsrv_mega_sme')
            ->select('SET NOCOUNT ON;exec Z_STXI_DOWNLOAD_PA100_BOM_FOR_SYNC_PSI');

        $getDataPA100 = array_map(function ($valueDe2) {
            return (array) $valueDe2;
        }, $getDataPA100);

        // return $getDataPA100;
        
        foreach ($getDataPA100 as $key => $value) {
            syncBOMToPSIQueue::dispatch($value, $runTime)->onQueue('syncPA100BOMToPSI');
        }
        
        return 'Sync BOM Queued, Data to be updated : '.count($getDataPA100);
    }

    public function syncDeleteData($dataPA100 = []) {
        $runTime = date('Y-m-d H:i:s');
        $getPSIDataForDelete = BOMSTX_TBL::where('APPROVED', 0)->get();

        if (count($dataPA100) === 0) {
            $dataSTXI = DB::connection('sqlsrv_mega_sme')
            ->select('SET NOCOUNT ON;exec Z_STXI_DOWNLOAD_PA100_BOM_FOR_SYNC_PSI');

            $dataSTXI = array_map(function ($valueDe2) {
                return (array) $valueDe2;
            }, $dataSTXI);
        } else {
            $dataSTXI = $dataPA100;
        }

        foreach ($getPSIDataForDelete as $keyPSI => $valuePSI) {
            syncDeletePSItoBOMQueue::dispatch($dataSTXI, $valuePSI, $runTime)->onQueue('deletePA100BOMToPSI');
        }

        return 'Sync BOM For delete Queued, Data to be updated : '.count($getPSIDataForDelete);
    }
}
