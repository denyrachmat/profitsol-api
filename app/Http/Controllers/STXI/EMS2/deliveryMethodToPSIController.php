<?php

namespace App\Http\Controllers\STXI\EMS2;

use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\STXI\importSPQMaster;
use Illuminate\Support\Facades\DB;

use App\Models\STXI\EMS2\SPQMaster;
use App\Models\STXI\EMS2\DLVTYOHist;

use App\Jobs\STXI\EMS2\DLVSMTTYOEmailQueue;
use App\Exports\STXT\exportDeliveryHist;

class deliveryMethodToPSIController extends BaseController
{
    public function UploadSPQ(Request $req)
    {
        // return $req->file('file')->hashName();
        $nama_file = $req->file->hashName();

        $req->file->storeAs('/public/upload_spq/', $nama_file);

        $importer = new importSPQMaster();

        Excel::import($importer, public_path('/storage/upload_spq/' . $nama_file));

        return $this->handleResponse([], 'Upload Sukses ' . $nama_file);
    }

    public function SPQIndex($whereModel = null)
    {
        $data = SPQMaster::select(
            'id',
            'MITM_MODELCD',
            'MITM_PCBCD',
            DB::raw('CAST(MITM_SPQ AS INT) MITM_SPQ'),
            'STXI_SPQ'
        )
            ->join(
                DB::raw('[MGSVR].[VMI_TYO].[dbo].[MITM_TBL]'),
                'MITM_ITMCD',
                'MITM_MODELCD'
            )
            ->where('MITM_MODEL', 1);

        if (!empty($whereModel)) {
            $data->where('MITM_MODELCD', $whereModel);
        }


        return $this->handleResponse(!empty($whereModel) ? $data->first() : $data->get(), 'Data found !');
    }

    public function searchItemMaster($filter)
    {
        $data = DB::connection('sqlsrv_mega_tyo')->table('MITM_TBL')->where('MITM_ITMCD', 'LIKE', $filter . '%')->get()->toArray();

        if (count($data) > 0) {
            return $this->handleResponse($data, 'Data found !');
        } else {
            return $this->handleError('Data not found !');
        }
    }

    public function SPQCreateUpdate(Request $req)
    {
        $data = SPQMaster::updateOrCreate([
            'MITM_MODELCD' => $req->MITM_MODELCD,
            'MITM_PCBCD' => $req->MITM_PCBCD
        ], [
            'MITM_MODELCD' => $req->MITM_MODELCD,
            'MITM_PCBCD' => $req->MITM_PCBCD,
            'STXI_SPQ' => $req->STXI_SPQ,
        ]);

        return $this->handleResponse($data, 'Data Updated !');
    }

    public function SPQDeleteData($id)
    {
        $data = SPQMaster::where('id', $id)->delete();

        return $this->handleResponse($data, 'Data deleted !');
    }

    public function DLVIndex()
    {
        return $this->handleResponse($this->DLVGetData(null, ['DEL_DATE'], true), 'Data found !');
    }

    public function DLVGetData(
        $date = null,
        $sel = [
            'MITM_MODELCD',
            'MITM_ITMD1',
            'DEL_DATE'
        ],
        $withDet = false
    ) {
        $data = DB::connection('sqlsrv_ems2')->table('V_DLV_TYO_HIST')->select(
            array_merge($sel, [
                DB::raw('SUM(I_QTY) AS TOT_INC_DLV'),
                DB::raw('SUM(O_QTY) AS TOT_OUT_BC_DLV'),
                DB::raw('SUM(OWB_QTY) AS TOT_OUT_WOBC_DLV'),
                DB::raw('SUM(TOT_QTY) AS TOT_SMT_DLV'),
                DB::raw('MAX(IPP_REMARK) AS IPP_REMARK'),
                DB::raw('MAX(RANK_REMARK) AS RANK_REMARK')
            ])
        )->join(
            DB::raw('[MGSVR].[VMI_TYO].[dbo].[MITM_TBL]'),
            'MITM_ITMCD',
            'MITM_MODELCD'
        )
        ->whereNull('deleted_at')
            ->groupBy($sel);

        if (!empty($date)) {
            $data->where('DEL_DATE', $date);
        }

        $dataHasil = array_map(function ($value) {
            return (array)$value;
        }, $data->get()->toArray());

        if ($withDet) {
            $dataWithDet = [];
            foreach ($dataHasil as $key => $value) {
                $dataWithDet[] = array_merge(
                    $value,
                    [
                        'det' => $this->DLVGetData($value['DEL_DATE'], [
                            'MITM_MODELCD',
                            'MITM_ITMD1',
                            'DEL_DATE'
                        ], false)
                    ]
                );
            }

            return $dataWithDet;
        }

        return $dataHasil;
    }

    public function DLVWithBarcode(Request $req)
    {
        ini_set('max_execution_time', '300');
        $hasil = [];
        foreach ($req->model as $key => $value) {
            $query = "SET NOCOUNT ON;EXEC Z_STXI_GET_CPO_DLV_STXI_ITEC @model = '" . $value . "', @date_start = '" . date('Y-m-01', strtotime($req->date)) . "', @date_to = '" . date('Y-m-d', strtotime($req->date . "-1 days")) . "'";
            $dataCPO = collect(DB::connection('sqlsrv_mega_tyo')->select(DB::raw(
                $query
            )))[0];

            $getSPQDataPersheet = $this->SPQIndex($value)->original['data'] ? $this->SPQIndex($value)->original['data']['MITM_SPQ'] : false;

            // return $getSPQDataPersheet;
            $hasilWithBarcode = $dataCPO->BAL_CPO_STXI_ITEC > 0
                ? (
                    $req->delivery[$key] > $dataCPO->BAL_CPO_STXI_ITEC
                    ? (
                        !$getSPQDataPersheet || (int)$dataCPO->BAL_CPO_STXI_ITEC < $getSPQDataPersheet
                        ? 0
                        : (int)$dataCPO->BAL_CPO_STXI_ITEC
                    )
                    : $req->delivery[$key]
                )
                : 0 ;
            $getSPQArray = $hasilWithBarcode > 0 ? $this->DLVCalSPQRes($hasilWithBarcode, $req->delivery[$key], $value) : 0;

            $hasil[] = [
                // 'query' => $query,
                'model' => $value,
                'delivery' => $req->delivery[$key],
                'cpo' => (int)$dataCPO->BAL_CPO_STXI_ITEC,
                'withBarcode' => $hasilWithBarcode,
                'spq' => $getSPQArray
            ];
        }

        return $this->handleResponse($hasil, 'Data found !');
    }

    public function DLVCalcSPQ($qty, $spq, $hasil = [])
    {
        if ($qty > $spq) {
            $total = $qty - $spq;
            $hasil[] = $spq;

            return $this->DLVCalcSPQ($total, $spq, $hasil);
        } else {
            $total = $qty;
            $hasil[] = $total;

            return $hasil;
        }
    }

    public function DLVCalSPQRes($qty, $delivery, $model)
    {
        $getSPQData = SPQMaster::where('MITM_MODELCD', $model)->first();
        if ($qty <= 0 || empty($getSPQData)) {
            return "0";
        }

        $getSPQArray = $this->DLVCalcSPQ($qty, isset($getSPQData->STXI_SPQ) ? (int)$getSPQData->STXI_SPQ : 0);

        $hasilSPQ = [];
        $totalBox = 1;
        $data = -1;

        foreach ($getSPQArray as $keySPQArr => $valueSPQArr) {
            if ($keySPQArr > 0 && $valueSPQArr === $getSPQArray[$keySPQArr - 1]) {
                $totalBox = $totalBox + 1;

                $hasilSPQ[$data] = $valueSPQArr . ' X ' . $totalBox;
            } else {
                $data++;
                $totalBox = 1;
                $hasilSPQ[$data] = $valueSPQArr . ' X ' . $totalBox;
            }
        }

        return $hasilSPQ;
    }

    public function DLVStore(Request $req)
    {
        $hasil = [];
        DLVTYOHist::where('DEL_DATE', $req->date)->delete();

        foreach ($req->data as $key => $value) {
            if (!empty($value['delivery'])) {
                $hasil[] = DLVTYOHist::create([
                    'MITM_MODELCD' => $value['model'],
                    'IO_QTY' => $value['delivery'],
                    'IO_REMARK' => 'FROM_SMT',
                    'IPP_REMARK' => $value['ipp'],
                    'RANK_REMARK' => $value['rank'],
                    'DEL_DATE' => $req->date,
                ]);
            }

            if (!empty($value['withBarcode'])) {
                $hasil[] = DLVTYOHist::create([
                    'MITM_MODELCD' => $value['model'],
                    'IO_QTY' => $value['withBarcode'] * -1,
                    'IO_REMARK' => 'TO_ITEC',
                    'IPP_REMARK' => $value['ipp'],
                    'RANK_REMARK' => $value['rank'],
                    'DEL_DATE' => $req->date,
                ]);
            }

            if (!empty($value['withoutBarcode'])) {
                $hasil[] = DLVTYOHist::create([
                    'MITM_MODELCD' => $value['model'],
                    'IO_QTY' => $value['withoutBarcode'] * -1,
                    'IO_REMARK' => 'TO_ITEC_WB',
                    'IPP_REMARK' => $value['ipp'],
                    'RANK_REMARK' => $value['rank'],
                    'DEL_DATE' => $req->date,
                ]);
            }
        }

        return $this->handleResponse($hasil, 'History delivery added !');
    }

    public function DLVSendEmail($date)
    {
        $data = $this->DLVGetData($date, [
            'MITM_MODELCD',
            'MITM_ITMD1',
            'DEL_DATE',
            'IPP_REMARK',
            'RANK_REMARK'
        ]);

        // return $data;

        $hasilData = [];
        $totalDelivery = 0;
        $totalWBarcode = 0;
        $totalWOBarcode = 0;
        $totalSMTDlv = 0;
        foreach ($data as $key => $value) {
            $totalDelivery += $value['TOT_INC_DLV'];
            $totalWBarcode += $value['TOT_OUT_BC_DLV'];
            $totalWOBarcode += $value['TOT_OUT_WOBC_DLV'];
            $totalSMTDlv += $value['TOT_SMT_DLV'];
            $hasilData[] = array_merge(
                $value,
                [
                    'SPQ' => $this->DLVCalSPQRes($value['TOT_OUT_BC_DLV'], $value['TOT_INC_DLV'], $value['MITM_MODELCD']),
                ]
            );
        }

        $insertJob = (new DLVSMTTYOEmailQueue(
            'PT SMT Indonesia',
            $hasilData,
            $totalDelivery,
            $totalWBarcode,
            $totalWOBarcode,
            $totalSMTDlv,
            $date
        ));

        dispatch($insertJob)->onQueue('sendEmailQueue');

        return $this->handleResponse($insertJob, 'Email sent !');
    }

    public function DLVExport()
    {
        $data = $this->DLVGetData(null, [
            'MITM_MODELCD',
            'MITM_ITMD1',
            'DEL_DATE',
            'FTRN'
        ]);

        // return $data;
        Excel::store(new exportDeliveryHist($data), 'export_delivery.xlsx', 'public');

        return 'storage/app/public/export_delivery.xlsx';
    }

    public function syncBOMToPSI()
    {
        ini_set('max_execution_time', 7200);
        // return Storage::download('Result.xlsx');

        // return 'test';
        $searchData = DB::connection('sqlsrv_mega_sme')->select("SET NOCOUNT ON;EXEC Z_STXI_BOM_SYNC_PSI @procedure = 'NEED_DELETED'");

        // return $searchData;
        $hasil = [
            'INSERTED' => [],
            'UPDATED' => [],
            'DELETED' => []
        ];

        $hasilByItem = [];
        $hasilTen = [];
        $insertKey = $updateKey = $deleteKey = $mdlKey = 0;
        foreach ($searchData as $key => $value) {
            if ($value->STAT_BOM === 'INSERTED') {
                DB::connection('sqlsrv_psi_eng')->table('BOMSTX_TBL')->insert([
                    'MODEL_CODE' => $value->MODEL_CODE,
                    'MODEL_DESC' => $value->MODEL_DESC,
                    'REVISION' => $value->REVISION,
                    'MAIN_PART_CODE' => $value->MAIN_PART_CODE,
                    'MAIN_SPTNO' => $value->MAIN_SPTNO,
                    'MAIN_MAKERNM' => $value->MAIN_MAKERNM,
                    'MS_NO' => $value->MS_NO,
                    'MODEL_QTY' => $value->MODEL_QTY,
                    'PART_QTY' => $value->PART_QTY,
                    'MAIN_PA_PERCENT' => $value->MAIN_PA_PERCENT,
                    'PO_FAILURE' => $value->PO_FAILURE,
                    'KO_FAILURE' => $value->KO_FAILURE,
                    'DETAIL_REMARK' => $value->DETAIL_REMARK,
                    'CONSIDER_PO_MRP' => $value->CONSIDER_PO_MRP,
                    'CONSIDER_KO_MRP' => $value->CONSIDER_KO_MRP,
                    'PROCESS_CODE' => $value->PROCESS_CODE,
                    'EPSON_ORG_PART' => $value->EPSON_ORG_PART,
                    'EPSON_SPTNO' => $value->EPSON_SPTNO,
                    'EPSON_MAKERNM' => $value->EPSON_MAKERNM,
                    'BOM_REMARK' => $value->BOM_REMARK,
                    'SUB' => $value->SUB,
                    'SUB_SPTNO' => $value->SUB_SPTNO,
                    'SUB_MAKERNM' => $value->SUB_MAKERNM,
                    'SUB_PA_PERCENT' => $value->SUB_PA_PERCENT,
                    'SUB1' => $value->SUB1,
                    'SUB1_SPTNO' => $value->SUB1_SPTNO,
                    'SUB1_SPTNO2' => $value->SUB1_SPTNO2,
                    'SUB2' => $value->SUB2,
                    'SUB2_SPTNO' => $value->SUB2_SPTNO,
                    'SUB2_SPTNO2' => $value->SUB2_SPTNO2,
                    'IEI_TEN_NO' => trim($value->IEI_TEN_NO) == '' ? 'N/A' : trim($value->IEI_TEN_NO),
                    'SEC_TEN_NO' => $value->SEC_TEN_NO,
                    'TEN_RECEIVE_DATE' => $value->TEN_RECEIVE_DATE,
                    'CHANGE_OVERVIEW' => $value->CHANGE_OVERVIEW,
                    'TEN_UPDATE_DATE' => $value->TEN_UPDATE_DATE,
                    'STOCK_SGL' => $value->STOCK_SGL,
                    'STOCK_CPO' => $value->STOCK_CPO,
                    'APPROVED' => 0,
                    'UPDDT' => date('Y-m-d H:i:s'),
                ]);

                $hasil['INSERTED'][$insertKey] = [
                    'NO' => $insertKey + 1,
                    'MODEL_CODE' => $value->MODEL_CODE,
                    'MODEL_DESC' => $value->MODEL_DESC,
                    'REVISION' => $value->REVISION,
                    'MAIN_PART_CODE' => $value->MAIN_PART_CODE,
                    'MAIN_SPTNO' => $value->MAIN_SPTNO,
                    'MAIN_MAKERNM' => $value->MAIN_MAKERNM,
                    'MS_NO' => $value->MS_NO,
                    'MODEL_QTY' => $value->MODEL_QTY,
                    'PART_QTY' => $value->PART_QTY,
                    'MAIN_PA_PERCENT' => $value->MAIN_PA_PERCENT,
                    'PO_FAILURE' => $value->PO_FAILURE,
                    'KO_FAILURE' => $value->KO_FAILURE,
                    'DETAIL_REMARK' => $value->DETAIL_REMARK,
                    'CONSIDER_PO_MRP' => $value->CONSIDER_PO_MRP,
                    'CONSIDER_KO_MRP' => $value->CONSIDER_KO_MRP,
                    'PROCESS_CODE' => $value->PROCESS_CODE,
                    'EPSON_ORG_PART' => $value->EPSON_ORG_PART,
                    'EPSON_SPTNO' => $value->EPSON_SPTNO,
                    'EPSON_MAKERNM' => $value->EPSON_MAKERNM,
                    'BOM_REMARK' => $value->BOM_REMARK,
                    'SUB' => $value->SUB,
                    'SUB_SPTNO' => $value->SUB_SPTNO,
                    'SUB_MAKERNM' => $value->SUB_MAKERNM,
                    'SUB_PA_PERCENT' => $value->SUB_PA_PERCENT,
                    'SUB1' => $value->SUB1,
                    'SUB1_SPTNO' => $value->SUB1_SPTNO,
                    'SUB1_SPTNO2' => $value->SUB1_SPTNO2,
                    'SUB2' => $value->SUB2,
                    'SUB2_SPTNO' => $value->SUB2_SPTNO,
                    'SUB2_SPTNO2' => $value->SUB2_SPTNO2,
                    'IEI_TEN_NO' => trim($value->IEI_TEN_NO) == '' ? 'N/A' : trim($value->IEI_TEN_NO),
                    'SEC_TEN_NO' => $value->SEC_TEN_NO,
                    'TEN_RECEIVE_DATE' => $value->TEN_RECEIVE_DATE,
                    'CHANGE_OVERVIEW' => $value->CHANGE_OVERVIEW,
                    'STOCK_SGL' => $value->STOCK_SGL,
                    'STOCK_CPO' => $value->STOCK_CPO,
                ];

                if ($key === 0 || $searchData[$key - 1]->MODEL_CODE !== $value->MODEL_CODE) {
                    $insertKey++;
                }
            } elseif ($value->STAT_BOM === 'NEED_UPDATED') {
                DB::connection('sqlsrv_psi_eng')->table('BOMSTX_TBL')
                ->where('MODEL_CODE', $value->MODEL_CODE)
                ->where('REVISION', $value->REVISION)
                ->where('MAIN_PART_CODE', $value->MAIN_PART_CODE)
                ->update([
                    'MODEL_CODE' => $value->MODEL_CODE,
                    'MODEL_DESC' => $value->MODEL_DESC,
                    'REVISION' => $value->REVISION,
                    'MAIN_PART_CODE' => $value->MAIN_PART_CODE,
                    'MAIN_SPTNO' => $value->MAIN_SPTNO,
                    'MAIN_MAKERNM' => $value->MAIN_MAKERNM,
                    'MS_NO' => $value->MS_NO,
                    'MODEL_QTY' => $value->MODEL_QTY,
                    'PART_QTY' => $value->PART_QTY,
                    'MAIN_PA_PERCENT' => $value->MAIN_PA_PERCENT,
                    'PO_FAILURE' => $value->PO_FAILURE,
                    'KO_FAILURE' => $value->KO_FAILURE,
                    'DETAIL_REMARK' => $value->DETAIL_REMARK,
                    'CONSIDER_PO_MRP' => $value->CONSIDER_PO_MRP,
                    'CONSIDER_KO_MRP' => $value->CONSIDER_KO_MRP,
                    'PROCESS_CODE' => $value->PROCESS_CODE,
                    'EPSON_ORG_PART' => $value->EPSON_ORG_PART,
                    'EPSON_SPTNO' => $value->EPSON_SPTNO,
                    'EPSON_MAKERNM' => $value->EPSON_MAKERNM,
                    'BOM_REMARK' => $value->BOM_REMARK,
                    'SUB' => $value->SUB,
                    'SUB_SPTNO' => $value->SUB_SPTNO,
                    'SUB_MAKERNM' => $value->SUB_MAKERNM,
                    'SUB_PA_PERCENT' => $value->SUB_PA_PERCENT,
                    'SUB1' => $value->SUB1,
                    'SUB1_SPTNO' => $value->SUB1_SPTNO,
                    'SUB1_SPTNO2' => $value->SUB1_SPTNO2,
                    'SUB2' => $value->SUB2,
                    'SUB2_SPTNO' => $value->SUB2_SPTNO,
                    'SUB2_SPTNO2' => $value->SUB2_SPTNO2,
                    'IEI_TEN_NO' => trim($value->IEI_TEN_NO) == '' ? 'N/A' : trim($value->IEI_TEN_NO),
                    'SEC_TEN_NO' => $value->SEC_TEN_NO,
                    'TEN_RECEIVE_DATE' => $value->TEN_RECEIVE_DATE,
                    'CHANGE_OVERVIEW' => $value->CHANGE_OVERVIEW,
                    'TEN_UPDATE_DATE' => $value->TEN_UPDATE_DATE,
                    'APPROVED' => 0,
                    'UPDDT' => date('Y-m-d H:i:s'),
                    'STOCK_SGL' => $value->STOCK_SGL,
                    'STOCK_CPO' => $value->STOCK_CPO,
                ]);

                $hasil['UPDATED'][$updateKey] = [
                    'NO' => $updateKey + 1,
                    'MODEL_CODE' => $value->MODEL_CODE,
                    'MODEL_DESC' => $value->MODEL_DESC,
                    'REVISION' => $value->REVISION,
                    'MAIN_PART_CODE' => $value->MAIN_PART_CODE,
                    'MAIN_SPTNO' => $value->MAIN_SPTNO,
                    'MAIN_MAKERNM' => $value->MAIN_MAKERNM,
                    'MS_NO' => $value->MS_NO,
                    'MODEL_QTY' => $value->MODEL_QTY,
                    'PART_QTY' => $value->PART_QTY,
                    'MAIN_PA_PERCENT' => $value->MAIN_PA_PERCENT,
                    'PO_FAILURE' => $value->PO_FAILURE,
                    'KO_FAILURE' => $value->KO_FAILURE,
                    'DETAIL_REMARK' => $value->DETAIL_REMARK,
                    'CONSIDER_PO_MRP' => $value->CONSIDER_PO_MRP,
                    'CONSIDER_KO_MRP' => $value->CONSIDER_KO_MRP,
                    'PROCESS_CODE' => $value->PROCESS_CODE,
                    'EPSON_ORG_PART' => $value->EPSON_ORG_PART,
                    'EPSON_SPTNO' => $value->EPSON_SPTNO,
                    'EPSON_MAKERNM' => $value->EPSON_MAKERNM,
                    'BOM_REMARK' => $value->BOM_REMARK,
                    'SUB' => $value->SUB,
                    'SUB_SPTNO' => $value->SUB_SPTNO,
                    'SUB_MAKERNM' => $value->SUB_MAKERNM,
                    'SUB_PA_PERCENT' => $value->SUB_PA_PERCENT,
                    'SUB1' => $value->SUB1,
                    'SUB1_SPTNO' => $value->SUB1_SPTNO,
                    'SUB1_SPTNO2' => $value->SUB1_SPTNO2,
                    'SUB2' => $value->SUB2,
                    'SUB2_SPTNO' => $value->SUB2_SPTNO,
                    'SUB2_SPTNO2' => $value->SUB2_SPTNO2,
                    'IEI_TEN_NO' => trim($value->IEI_TEN_NO) == '' ? 'N/A' : trim($value->IEI_TEN_NO),
                    'SEC_TEN_NO' => $value->SEC_TEN_NO,
                    'TEN_RECEIVE_DATE' => $value->TEN_RECEIVE_DATE,
                    'CHANGE_OVERVIEW' => $value->CHANGE_OVERVIEW,
                    'STOCK_SGL' => $value->STOCK_SGL,
                    'STOCK_CPO' => $value->STOCK_CPO,
                ];

                if ($key === 0 || $searchData[$key - 1]->MODEL_CODE !== $value->MODEL_CODE) {
                    $updateKey++;
                }
            } elseif ($value->STAT_BOM === 'NEED_DELETED') {
                DB::connection('sqlsrv_psi_eng')->table('BOMSTX_TBL')
                ->where('MODEL_CODE', $value->MODEL_CODE)
                ->where('REVISION', $value->REVISION)
                ->where('MAIN_PART_CODE', $value->MAIN_PART_CODE)
                ->delete();

                $hasil['DELETED'][$deleteKey] = [
                    'NO' => $deleteKey + 1,
                    'MODEL_CODE' => $value->MODEL_CODE,
                    'MODEL_DESC' => $value->MODEL_DESC,
                    'REVISION' => $value->REVISION,
                    'MAIN_PART_CODE' => $value->MAIN_PART_CODE,
                    'MAIN_SPTNO' => $value->MAIN_SPTNO,
                    'MAIN_MAKERNM' => $value->MAIN_MAKERNM,
                    'MS_NO' => $value->MS_NO,
                    'MODEL_QTY' => $value->MODEL_QTY,
                    'PART_QTY' => $value->PART_QTY,
                    'MAIN_PA_PERCENT' => $value->MAIN_PA_PERCENT,
                    'PO_FAILURE' => $value->PO_FAILURE,
                    'KO_FAILURE' => $value->KO_FAILURE,
                    'DETAIL_REMARK' => $value->DETAIL_REMARK,
                    'CONSIDER_PO_MRP' => $value->CONSIDER_PO_MRP,
                    'CONSIDER_KO_MRP' => $value->CONSIDER_KO_MRP,
                    'PROCESS_CODE' => $value->PROCESS_CODE,
                    'EPSON_ORG_PART' => $value->EPSON_ORG_PART,
                    'EPSON_SPTNO' => $value->EPSON_SPTNO,
                    'EPSON_MAKERNM' => $value->EPSON_MAKERNM,
                    'BOM_REMARK' => $value->BOM_REMARK,
                    'SUB' => $value->SUB,
                    'SUB_SPTNO' => $value->SUB_SPTNO,
                    'SUB_MAKERNM' => $value->SUB_MAKERNM,
                    'SUB_PA_PERCENT' => $value->SUB_PA_PERCENT,
                    'SUB1' => $value->SUB1,
                    'SUB1_SPTNO' => $value->SUB1_SPTNO,
                    'SUB1_SPTNO2' => $value->SUB1_SPTNO2,
                    'SUB2' => $value->SUB2,
                    'SUB2_SPTNO' => $value->SUB2_SPTNO,
                    'SUB2_SPTNO2' => $value->SUB2_SPTNO2,
                    'IEI_TEN_NO' => trim($value->IEI_TEN_NO) == '' ? 'N/A' : trim($value->IEI_TEN_NO),
                    'SEC_TEN_NO' => $value->SEC_TEN_NO,
                    'TEN_RECEIVE_DATE' => $value->TEN_RECEIVE_DATE,
                    'CHANGE_OVERVIEW' => $value->CHANGE_OVERVIEW,
                    'STOCK_SGL' => $value->STOCK_SGL,
                    'STOCK_CPO' => $value->STOCK_CPO,
                ];

                if ($key === 0 || $searchData[$key - 1]->MODEL_CODE !== $value->MODEL_CODE) {
                    $deleteKey++;
                }
            }

            if ($key === 0 || $value->MODEL_CODE !== $searchData[$key - 1]->MODEL_CODE) {
                $hasilByItem[$value->MODEL_CODE] = [
                    'MODEL_CODE' => $value->MODEL_CODE,
                    'MODEL_DESC' => $value->MODEL_DESC,
                    'REVISION' => $value->REVISION,
                    'INSERTED'  => $insertKey,
                    'UPDATED'  => $updateKey,
                    'DELETED'  => $deleteKey,
                    'TEN_DETAILS' => []
                ];

                if ($key === 0 || $value->IEI_TEN_NO !== $searchData[$key - 1]->IEI_TEN_NO) {
                    $hasilByItem[$value->MODEL_CODE]['TEN_DETAILS'][$key] = [
                        'IEI_TEN_NO' => $value->IEI_TEN_NO,
                        'CHANGE_OVERVIEW' => $value->CHANGE_OVERVIEW,
                        'INSERTED'  => $insertKey,
                        'UPDATED'  => $updateKey,
                        'DELETED'  => $deleteKey,
                    ];
                }
            }

            // $hasilTen[$value->IEI_TEN_NO][$key] = $value->CHANGE_OVERVIEW;
        }

        return $hasilByItem;

        // return view('SCHEDULLER.bomsync', ['user' => 'PT SMT Indonesia', 'data' => $hasilByItem]);

        $stored = (new STXItoPSIBOMResult($hasil))->store('Result_sync_'.date('Ymd').'.xlsx', 'public');
        // $downloadStored = (new STXItoPSIBOMResult($hasil))->download('Result_sync_'.date('Ymd').'.xlsx');

        if ($stored) {
            $insertJob = (new STXIPSIBOMSyncEmailJobs('PT SMT Indonesia', $hasilByItem, 'Result_sync_'.date('Ymd').'.xlsx'));

            dispatch($insertJob);
            return 'Email sent !!';
        }
    }
}
