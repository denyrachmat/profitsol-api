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
            ->select("SET NOCOUNT ON;exec Z_STXI_DOWNLOAD_PA100_BOM_FOR_SYNC_PSI 1");

        $getDataPA100 = array_map(function ($valueDe2) {
            return (array) $valueDe2;
        }, $getDataPA100);

        $getListModelPart = [];
        $count = 0;
        foreach ($getDataPA100 as $keyPart => $valuePart) {
            $getListModelPart[$valuePart['MODEL CODE']]['MODEL'] = $valuePart['MODEL CODE'];

            if ($keyPart > 0 && $valuePart['MODEL CODE'] == $getDataPA100[$keyPart - 1]['MODEL CODE']) {
                $count++;
            } else {
                $count = 0;
            }

            $getListModelPart[$valuePart['MODEL CODE']]['MAIN_PART'][$count] = $valuePart['MAIN PART CODE'];
        }

        foreach (array_values($getListModelPart) as $keyPartModel => $valuePartModel) {
            // Delete Model Part
            BOMSTX_TBL::where('MODEL_CODE', $valuePartModel['MODEL'])
                ->whereIn('MAIN_PART_CODE', array_values($valuePartModel['MAIN_PART']))
                ->whereNull('APRVDT')
                ->delete();
        }

        foreach ($getDataPA100 as $key => $value) {
            syncBOMToPSIQueue::dispatch($value, $runTime)->onQueue('syncPA100BOMToPSI');
        }

        return 'Sync BOM Queued, Data to be updated : ' . count($getDataPA100);
    }

    public function syncBOMbyItem($item)
    {
        $runTime = date('Y-m-d H:i:s');
        $getDataPA100 = DB::connection('sqlsrv_mega_sme')
            ->select("SET NOCOUNT ON;exec Z_STXI_DOWNLOAD_PA100_BOM_FOR_SYNC_PSI 0, '" . $item . "'");

        $getDataPA100 = array_map(function ($valueDe2) {
            return (array) $valueDe2;
        }, $getDataPA100);

        $getListModelPart = [];
        foreach ($getDataPA100 as $keyPart => $valuePart) {
            $getListModelPart[$valuePart['MODEL CODE'] . $valuePart['MAIN PART CODE']] = $valuePart['MAIN PART CODE'];
        }

        // Delete Model
        BOMSTX_TBL::where('MODEL_CODE', $item)
            // ->whereIn('MAIN_PART_CODE', array_values($getListModelPart))
            ->whereNull('APRVDT')
            ->delete();

            // return 'test';
        foreach ($getDataPA100 as $key => $value) {
            syncBOMToPSIQueue::dispatch($value, $runTime)->onQueue('syncPA100BOMToPSIItem');
        }

        return 'Sync BOM Queued, Data to be updated : ' . count($getDataPA100);
    }

    public function syncAllNotInterfaced(): array
    {
        $cekItem = DB::connection('sqlsrv_psi_eng')->table('MITM_TBL_STX')
            ->select('MITM_ITMCD', 'MITM_MODEL')
            ->leftJoin('BOMSTX_TBL', 'MODEL_CODE', 'MITM_ITMCD')
            ->where('MITM_MODEL', 1)
            ->whereNull('MODEL_CODE')
            ->where('MITM_ACTIVE', 'A')
            ->get()->toArray();

        return $cekItem;
    }

    public function syncDeleteData($dataPA100 = [])
    {
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

        return 'Sync BOM For delete Queued, Data to be updated : ' . count($getPSIDataForDelete);
    }

    public function syncWithoutJobs()
    {
        $runTime = date('Y-m-d H:i:s');
        $getDataPA100 = DB::connection('sqlsrv_mega_sme')
            ->select("SET NOCOUNT ON;exec Z_STXI_DOWNLOAD_PA100_BOM_FOR_SYNC_PSI 0,'222381602'");

        $getDataPA100 = array_map(function ($valueDe2) {
            return (array) $valueDe2;
        }, $getDataPA100);

        // return $getDataPA100;

        $hasil = [];
        foreach ($getDataPA100 as $key => $valData) {
            $cekData = BOMSTX_TBL::NoLock()
                ->where('MODEL_CODE', $valData['MODEL CODE'])
                ->where('REVISION', $valData['REVISION'])
                ->where('MAIN_PART_CODE', $valData['MAIN PART CODE'])
                ->where('MAIN_SPTNO', $valData['MAIN SPTNO'])
                ->where('MS_NO', $valData['MS NO'])
                ->where('IEI_TEN_NO', $valData['IEI TEN NO'])
                ->first();

            if (!empty($cekData)) {
                $hasil[] = BOMSTX_TBL::where('MODEL_CODE', $valData['MODEL CODE'])
                    ->where('REVISION', $valData['REVISION'])
                    ->where('MAIN_PART_CODE', $valData['MAIN PART CODE'])
                    ->where('MAIN_SPTNO', $valData['MAIN SPTNO'])
                    ->where('MS_NO', $valData['MS NO'])
                    ->where('IEI_TEN_NO', $valData['IEI TEN NO'])
                    ->update([
                        'MODEL_CODE' => $valData['MODEL CODE'],
                        'MODEL_DESC' => $valData['MODEL DESC'],
                        'REVISION' => $valData['REVISION'],
                        'MAIN_PART_CODE' => $valData['MAIN PART CODE'],
                        'MAIN_SPTNO' => $valData['MAIN SPTNO'],
                        'MAIN_MAKERNM' => $valData['MAIN MAKERNM'],
                        'MS_NO' => $valData['MS NO'],
                        'MODEL_QTY' => $valData['MODEL QTY'],
                        'PART_QTY' => $valData['PART QTY'],
                        'MAIN_PA_PERCENT' => $valData['MAIN PA%'],
                        'PO_FAILURE' => $valData['PO FAILURE'],
                        'KO_FAILURE' => $valData['KO FAILURE'],
                        'DETAIL_REMARK' => $valData['DETAIL REMARK'],
                        'CONSIDER_PO_MRP' => $valData['CONSIDER PO MRP'],
                        'CONSIDER_KO_MRP' => $valData['CONSIDER KO MRP'],
                        'PROCESS_CODE' => $valData['PROCESS CODE'],
                        'EPSON_ORG_PART' => $valData['EPSON ORG PART'],
                        'EPSON_SPTNO' => $valData['EPSON SPTNO'],
                        'EPSON_MAKERNM' => $valData['EPSON MAKERNM'],
                        'BOM_REMARK' => $valData['BOM REMARK'],
                        'SUB' => $valData['SUB'],
                        'SUB_SPTNO' => $valData['SUB SPTNO'],
                        'SUB_MAKERNM' => $valData['SUB MAKERNM'],
                        'SUB_PA_PERCENT' => $valData['SUB PA%'],
                        'SUB1' => $valData['SUB1'],
                        'SUB1_SPTNO' => $valData['SUB1 SPTNO'],
                        'SUB2' => '',
                        'SUB2_SPTNO' => $valData['SUB2 SPTNO'],
                        'IEI_TEN_NO' => trim($valData['IEI TEN NO']) == '' ? 'N/A' : trim($valData['IEI TEN NO']),
                        'SEC_TEN_NO' => $valData['SEC TEN NO'],
                        'TEN_RECEIVE_DATE' => $valData['TEN RECEIVE DATE'],
                        'CHANGE_OVERVIEW' => $valData['CHANGE OVERVIEW'],
                        // 'STOCK_SGL' => 0,
                        // 'STOCK_CPO' => 0,
                        'TEN_UPDATE_DATE' => $valData['TEN_UPDATE_DATE'],
                        'UPDDT' => date('Y-m-d H:i:s')
                    ]);
            } else {
                $hasil[] = BOMSTX_TBL::NoLock()->create([
                    'MODEL_CODE' => $valData['MODEL CODE'],
                    'MODEL_DESC' => $valData['MODEL DESC'],
                    'REVISION' => $valData['REVISION'],
                    'MAIN_PART_CODE' => $valData['MAIN PART CODE'],
                    'MAIN_SPTNO' => $valData['MAIN SPTNO'],
                    'MAIN_MAKERNM' => $valData['MAIN MAKERNM'],
                    'MS_NO' => $valData['MS NO'],
                    'MODEL_QTY' => $valData['MODEL QTY'],
                    'PART_QTY' => $valData['PART QTY'],
                    'MAIN_PA_PERCENT' => $valData['MAIN PA%'],
                    'PO_FAILURE' => $valData['PO FAILURE'],
                    'KO_FAILURE' => $valData['KO FAILURE'],
                    'DETAIL_REMARK' => $valData['DETAIL REMARK'],
                    'CONSIDER_PO_MRP' => $valData['CONSIDER PO MRP'],
                    'CONSIDER_KO_MRP' => $valData['CONSIDER KO MRP'],
                    'PROCESS_CODE' => $valData['PROCESS CODE'],
                    'EPSON_ORG_PART' => $valData['EPSON ORG PART'],
                    'EPSON_SPTNO' => $valData['EPSON SPTNO'],
                    'EPSON_MAKERNM' => $valData['EPSON MAKERNM'],
                    'BOM_REMARK' => $valData['BOM REMARK'],
                    'SUB' => $valData['SUB'],
                    'SUB_SPTNO' => $valData['SUB SPTNO'],
                    'SUB_MAKERNM' => $valData['SUB MAKERNM'],
                    'SUB_PA_PERCENT' => $valData['SUB PA%'],
                    'SUB1' => $valData['SUB1'],
                    'SUB1_SPTNO' => $valData['SUB1 SPTNO'],
                    'SUB2' => $valData[''],
                    'SUB2_SPTNO' => $valData['SUB2 SPTNO'],
                    'IEI_TEN_NO' => trim($valData['IEI TEN NO']) == '' ? 'N/A' : trim($valData['IEI TEN NO']),
                    'SEC_TEN_NO' => $valData['SEC TEN NO'],
                    'TEN_RECEIVE_DATE' => $valData['TEN RECEIVE DATE'],
                    'CHANGE_OVERVIEW' => $valData['CHANGE OVERVIEW'],
                    // 'STOCK_SGL' => 0,
                    // 'STOCK_CPO' => 0,
                    'TEN_UPDATE_DATE' => $valData['TEN_UPDATE_DATE'],
                    'UPDDT' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        return $hasil;
    }
}
