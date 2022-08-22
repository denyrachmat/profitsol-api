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
            DB::raw('CASE WHEN SPQ_BOX_PROT_FLAG = 1
                THEN STXI_SPQ
                ELSE CAST(MITM_SPQ AS INT)
            END AS MITM_SPQ_CHECK'),
            'STXI_SPQ',
            'SPQ_BOX_PROT_FLAG'
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
            'SPQ_BOX_PROT_FLAG' => $req->SPQ_BOX_PROT_FLAG,
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
        return $this->handleResponse($this->DLVGetData(null, ['DEL_DATE'], true, true), 'Data found !');
    }

    public function DLVGetData(
        $date = null,
        $sel = [
            'MITM_MODELCD',
            'MITM_ITMD1',
            'DEL_DATE'
        ],
        $withDet = false,
        $isDLVStock = false
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
            ->groupBy($sel);

            // $data->whereIn('IO_REMARK', ['FROM_SMT', 'TO_ITEC', 'TO_ITEC_STOCKDLV']);

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
                        ], false, $isDLVStock)
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

            $getSPQDataPersheet = $this->SPQIndex($value)->original['data'] ? $this->SPQIndex($value)->original['data']['MITM_SPQ_CHECK'] : false;

            // if ($value === 'F63654-12') {
            //     return [
            //         !$getSPQDataPersheet,
            //         (int)$req->delivery[$key] < (int)$getSPQDataPersheet,
            //         ((int)($req->delivery[$key]/ (int)$getSPQDataPersheet) !== ($req->delivery[$key]/ (int)$getSPQDataPersheet)),
            //         ($req->delivery[$key]/ (int)$getSPQDataPersheet)
            //     ];
            // }
            $hasilWithBarcode = (int)$dataCPO->BAL_CPO_STXI_ITEC > 0
                ? (!$getSPQDataPersheet || (int)$req->delivery[$key] < (int)$getSPQDataPersheet || ((int)($req->delivery[$key]/ (int)$getSPQDataPersheet) !== ($req->delivery[$key]/ (int)$getSPQDataPersheet))
                    ? 0
                    : ($req->delivery[$key] > (int)$dataCPO->BAL_CPO_STXI_ITEC && $req->delivery[$key] > (int)$getSPQDataPersheet
                        ? (int)$dataCPO->BAL_CPO_STXI_ITEC
                        : $req->delivery[$key]
                    )
                )
                : 0;

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

        foreach ($req->data as $key => $value) {
            if (!empty($value['withBarcode'])) {
                DLVTYOHist::where('DEL_DATE', $req->date)->where('MITM_MODELCD', $value['model'])->where('IO_REMARK', $req->dlvStoc ? 'TO_ITEC_STOCKDLV': 'TO_ITEC')->delete();
                $hasil[] = DLVTYOHist::create([
                    'MITM_MODELCD' => $value['model'],
                    'IO_QTY' => $value['withBarcode'] * -1,
                    'IO_REMARK' => $req->dlvStoc ? 'TO_ITEC_STOCKDLV': 'TO_ITEC',
                    'IPP_REMARK' => $value['ipp'],
                    'RANK_REMARK' => $value['rank'],
                    'DEL_DATE' => $req->date,
                ]);
            }

            if (!$req->dlvStoc) {
                if (!empty($value['delivery'])) {
                    DLVTYOHist::where('DEL_DATE', $req->date)->where('MITM_MODELCD', $value['model'])->where('IO_REMARK', 'FROM_SMT')->delete();
                    $hasil[] = DLVTYOHist::create([
                        'MITM_MODELCD' => $value['model'],
                        'IO_QTY' => $value['delivery'],
                        'IO_REMARK' => 'FROM_SMT',
                        'IPP_REMARK' => $value['ipp'],
                        'RANK_REMARK' => $value['rank'],
                        'DEL_DATE' => $req->date,
                    ]);
                }

                if (!empty($value['withoutBarcode'])) {
                    DLVTYOHist::where('DEL_DATE', $req->date)->where('MITM_MODELCD', $value['model'])->where('IO_REMARK', 'TO_ITEC_WB')->delete();
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

    public function DLVStockDelivery($date, $item = '')
    {
        ini_set('max_execution_time', '300');
        if (!empty($item)) {
            $query = "SET NOCOUNT ON;EXEC Z_STXI_GET_CPO_DLV_STXI_ITEC @date_start = '" . date('Y-m-01', strtotime($date)) . "', @date_to = '" . date('Y-m-d', strtotime($date . ' -1 days')) . "', @model = '" . $item . "'";
        } else {
            $query = "SET NOCOUNT ON;EXEC Z_STXI_GET_CPO_DLV_STXI_ITEC @date_start = '" . date('Y-m-01', strtotime($date)) . "', @date_to = '" . date('Y-m-d', strtotime($date . ' -1 days')) . "'";
        }

        $dataCPO = collect(DB::connection('sqlsrv_mega_tyo')->select(DB::raw(
            $query
        )));

        $getCPO = $dataCPO->where('BAL_STOCK', '>', 0)->where('BAL_CPO_STXI_ITEC', '>', 0)->map(function ($t) {
            return collect($t)->only([
                'MITM_ITMCD',
                'MITM_ITMD1',
                'BAL_CPO_STXI_ITEC',
                'BAL_STOCK'
            ]);
        })->toArray();

        return array_values($getCPO);
    }
}
