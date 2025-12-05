<?php

namespace App\Http\Controllers\STXI\PC;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Jobs\STXI\PC\syncBOMToPSIQueue;
use App\Jobs\STXI\PC\syncDeletePSItoBOMQueue;
use App\Jobs\STXI\PC\syncBOMToPSINotifQueue;

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
            $getListModelPart['DATA'][$valuePart['MODEL CODE']]['MODEL'] = $valuePart['MODEL CODE'];

            $getListModelPart['EMAIL'][$valuePart['MODEL CODE']]['MODEL'] = $valuePart['MODEL CODE'];
            $getListModelPart['EMAIL'][$valuePart['MODEL CODE']]['MODEL_DESC'] = $valuePart['MODEL DESC'];
            $getListModelPart['EMAIL'][$valuePart['MODEL CODE']]['REVISION'] = $valuePart['REVISION'];
            $getListModelPart['EMAIL'][$valuePart['MODEL CODE']]['IEI_TEN_NO'] = trim($valuePart['IEI TEN NO']) == '' ? 'N/A' : trim($valuePart['IEI TEN NO']);
            $getListModelPart['EMAIL'][$valuePart['MODEL CODE']]['CHANGE_OVERVIEW'] = $valuePart['CHANGE OVERVIEW'];

            if ($keyPart > 0 && $valuePart['MODEL CODE'] == $getDataPA100[$keyPart - 1]['MODEL CODE']) {
                $count++;
            } else {
                $count = 0;
            }

            $getListModelPart[$valuePart['MODEL CODE']]['MAIN_PART'][$count] = $valuePart['MAIN PART CODE'];
        }

        // Send Email Notif
        // syncBOMToPSINotifQueue::dispatch(array_values($getListModelPart['EMAIL']), [
        //     'ludh-praditto@sumitronics.co.jp',
        //     'deny-rachmat@sumitronics.co.jp'
        // ],[])->onQueue('sendEmailQueue');

        // return 'done';

        foreach (array_values($getListModelPart['DATA']) as $keyPartModel => $valuePartModel) {
            // Delete Model Part if not approved yet
            BOMSTX_TBL::where('MODEL_CODE', $valuePartModel['MODEL'])
                // ->whereIn('MAIN_PART_CODE', array_values($valuePartModel['MAIN_PART']))
                ->whereNull('APRVDT')
                ->delete();
        }

        foreach ($getDataPA100 as $keyData => $valueData) {
            $cekDataBOM = BOMSTX_TBL::where('MODEL_CODE', $valueData['MODEL CODE'])
                // ->where('MAIN_PART_CODE', $valueData['MAIN PART CODE'])
                ->where('REVISION', $valueData['REVISION'])
                ->orderBy('TEN_UPDATE_DATE', 'asc')
                ->first();

            if (!empty($cekDataBOM) && $cekDataBOM->TEN_UPDATE_DATE <> $valueData['TEN_UPDATE_DATE']) {
                $getDataPA100[$keyData]['REVISION'] = (float)$valueData['REVISION'] + 0.01;
            }
        }

        foreach ($getDataPA100 as $key => $value) {
            syncBOMToPSIQueue::dispatch($value, $runTime)->onQueue('syncPA100BOMToPSI');
        }

        syncBOMToPSINotifQueue::dispatch(array_values($getListModelPart['EMAIL']), [
            'hadi.cahyono@smt.co.id',
            'ida.damayanti@smt.co.id',
            'irma@smt.co.id',
            'lia.meliyanti@smt.co.id',
            'siti.fatmawati@smt.co.id',
            'PSI-PPC.Partcontrol@smt.co.id',
            'andy@smt.co.id',
        ],[
            // 'dadan-setiawan@sumitronics.co.jp',
            'wawan-setiawan@sumitronics.co.jp',
            'rexon-julianto@sumitronics.co.jp',
            'mohammad-mujib@sumitronics.co.jp',
            // 'bella-setivany@sumitronics.co.jp',
            'ludh-praditto@sumitronics.co.jp',
            'widiatama-rahayu@sumitronics.co.jp',
            'retno-astuti@sumitronics.co.jp',
            'huda@smt.co.id',
            'krista.diana@smt.co.id',
            'deny-rachmat@sumitronics.co.jp'
        ])->onQueue('sendEmailQueue');

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
            $getListModelPart['EMAIL'][$valuePart['MODEL CODE']]['MODEL'] = $valuePart['MODEL CODE'];
            $getListModelPart['EMAIL'][$valuePart['MODEL CODE']]['MODEL_DESC'] = $valuePart['MODEL DESC'];
            $getListModelPart['EMAIL'][$valuePart['MODEL CODE']]['REVISION'] = $valuePart['REVISION'];
            $getListModelPart['EMAIL'][$valuePart['MODEL CODE']]['IEI_TEN_NO'] = trim($valuePart['IEI TEN NO']) == '' ? 'N/A' : trim($valuePart['IEI TEN NO']);
            $getListModelPart['EMAIL'][$valuePart['MODEL CODE']]['CHANGE_OVERVIEW'] = $valuePart['CHANGE OVERVIEW'];

            $getListModelPart[$valuePart['MODEL CODE'] . $valuePart['MAIN PART CODE']] = $valuePart['MAIN PART CODE'];
        }

        // Delete Model
        BOMSTX_TBL::where('MODEL_CODE', $item)
            // ->whereIn('MAIN_PART_CODE', array_values($getListModelPart))
            ->whereNull('APRVDT')
            ->delete();

        foreach ($getDataPA100 as $keyData => $valueData) {
            $cekDataBOM = BOMSTX_TBL::where('MODEL_CODE', $valueData['MODEL CODE'])
                // ->where('MAIN_PART_CODE', $valueData['MAIN PART CODE'])
                ->where('REVISION', $valueData['REVISION'])
                ->orderBy('TEN_UPDATE_DATE', 'asc')
                ->first();

            if (!empty($cekDataBOM) && $cekDataBOM->TEN_UPDATE_DATE <> $valueData['TEN_UPDATE_DATE']) {
                $getDataPA100[$keyData]['REVISION'] = (float)$valueData['REVISION'] + 0.01;
            }
        }

        foreach ($getDataPA100 as $key => $value) {
            syncBOMToPSIQueue::dispatch($value, $runTime)->onQueue('syncPA100BOMToPSIItem');
        }

        return [
            'message' => 'Sync BOM Queued, Data to be updated : ' . count($getDataPA100),
            'data' => isset($getListModelPart['EMAIL']) ? array_values($getListModelPart['EMAIL']) : []
        ];
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

    public function syncWithoutJobs(Request $request)
    {
        $hasil = '';

        $dataSend = [];
        foreach ($request->data as $key => $value) {
            $proses = $this->syncBOMbyItem($value);

            // return $proses;
            $dataSend[] = $proses['data'];
            $hasil .= "Item : {{$value}} - {{$proses['message']}}<br>";
        }

        // Send Email Notif
        syncBOMToPSINotifQueue::dispatch($dataSend, [
            'deny-rachmat@sumitronics.co.jp'
        ],[])->onQueue('sendEmailQueue');

        return $hasil;
    }

    public function updateStockSGL()
    {
        ini_set('max_execution_time', 7200);
        ini_set("memory_limit", "2G");
        $getData = DB::connection('sqlsrv_psi_eng')->table('BOMSTX_TBL')->select('MAIN_PART_CODE')->groupBy('MAIN_PART_CODE')->get();

        $hasil = [];
        foreach ($getData as $key => $value) {
            $searchDataCPO = DB::connection('sqlsrv_mega_sme')->table('PPO2_TBL')->select(DB::raw('Sum([PPO2_POQTY]-[PPO2_GRNQT]) AS TOT'))->where('PPO2_ITMCD', $value->MAIN_PART_CODE)->first();
            $searchDataSGL = DB::connection('sqlsrv_mega_sme')->table('IBAL_TBL')->select(DB::raw('SUM(IBAL_BLQTY) AS TOT'))->where('IBAL_OWNER', 'STX')->where('IBAL_LOCCD', 'PSGL')->where('IBAL_ITMCD', $value->MAIN_PART_CODE)->first();

            $hasil[] = DB::connection('sqlsrv_psi_eng')->table('BOMSTX_TBL')->where('MAIN_PART_CODE', $value->MAIN_PART_CODE)->update([
                'STOCK_SGL' => isset($searchDataSGL) ? $searchDataSGL->TOT : 0,
                'STOCK_CPO' => isset($searchDataCPO) ? $searchDataCPO->TOT : 0,
                'UPDDT_STOCK' => date('Y-m-d H:i:s')
            ]);
        }

        return $hasil;
    }
}
