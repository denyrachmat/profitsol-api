<?php

namespace App\Http\Controllers\STXI\EMS2;

use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\STXI\importSPQMaster;
use Illuminate\Support\Facades\DB;

use App\Models\STXI\EMS2\SPQMaster;
use App\Models\STXI\EMS2\DLVTYOHist;
use App\Models\STXI\EMS2\DLVTYODet;

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
        $data = SPQMaster::where('id', $id);

        return $this->handleResponse($data, 'Data deleted !');
    }

    public function DLVIndex($date = '')
    {
        $data = $this->DLVGetData($date, !empty($date) ? [
            'MITM_MODELCD',
            'MITM_ITMD1',
            'DEL_DATE'
        ] : ['DEL_DATE'], !empty($date), true, false, '', true);

        return $this->handleResponse($data, 'Data found !');
    }

    public function DLVGetData(
        $date = null,
        $sel = [
            'MITM_MODELCD',
            'MITM_ITMD1',
            'DEL_DATE'
        ],
        $withDet = false,
        $isDLVStock = false,
        $fromDate = false,
        $item = '',
        $withFifo = false
    ) {
        $data = DB::connection('sqlsrv_ems2')->table('V_DLV_TYO_HIST')->select(
            array_merge($sel, [
                DB::raw('SUM(I_QTY) AS TOT_INC_DLV'),
                DB::raw('SUM(IS_QTY) AS TOT_INC_STOCK_DLV'),
                DB::raw('SUM(O_QTY) AS TOT_OUT_BC_DLV'),
                DB::raw('SUM(OWB_QTY) AS TOT_OUT_WOBC_DLV'),
                DB::raw('SUM(TOT_QTY) AS TOT_SMT_DLV'),
                DB::raw('SUM(OQS_QTY) AS TOT_OUT_STOCK_DLV'),
                DB::raw('(SUM(O_QTY) + SUM(OWB_QTY)) + SUM(OQS_QTY) AS TOT_OUT'),
                DB::raw('MAX(IPP_REMARK) AS IPP_REMARK'),
                DB::raw('MAX(RANK_REMARK) AS RANK_REMARK')
            ])
        )->join(
            DB::raw('[MGSVR].[VMI_TYO].[dbo].[MITM_TBL]'),
            'MITM_ITMCD',
            'MITM_MODELCD'
        )
            ->groupBy($sel)
            ->orderBy('DEL_DATE');

        // $data->whereIn('IO_REMARK', ['FROM_SMT', 'TO_ITEC', 'TO_ITEC_STOCKDLV']);

        if (!empty($date)) {
            if ($fromDate) {
                $data->where('DEL_DATE', '>=', $date);
            } else {
                $data->where('DEL_DATE', $date);
            }
        }

        if (!empty($item)) {
            $data->where('MITM_ITMCD', $item);
        }

        // if ($withFifo) {
        //     $data->leftJoin('DLV_REQ_DET', 'DLV_REQ_SMT_TYO.id', 'DRST_ID');
        // }

        $dataHasil = array_map(function ($value) {
            return (array)$value;
        }, $data->get()->toArray());

        if ($withDet) {
            $dataWithDet = [];
            foreach ($dataHasil as $key => $value) {
                $dataDet = $this->DLVGetData($value['DEL_DATE'], [
                    'MITM_MODELCD',
                    'MITM_ITMD1',
                    'DEL_DATE'
                ], false, $isDLVStock, false);

                // $dataFifo = [];
                // foreach ($dataDet as $keyDet => $valueDet) {
                //     $dataFifo[] = array_merge(
                //         $valueDet
                //     );
                // }

                if ($withFifo) {
                    $hasilFIFO = 0;
                    $dataFIFO = $this->fifoUpdateDLV($value['DEL_DATE'], $value['MITM_MODELCD'], false, true);
                    foreach ($dataFIFO as $keyFIFO => $valueFIFO) {
                        $hasilFIFO += $valueFIFO['DRD_QTY'];
                    }

                    $dataWithDet[] = array_merge(
                        ['no' => $key + 1],
                        $value,
                        [
                            'TOTAL_FIFO' => $hasilFIFO
                        ]
                    );
                } else {
                    $dataWithDet[] = array_merge(
                        ['no' => $key + 1],
                        $value
                    );
                }
            }

            return $dataWithDet;
        }

        $dataWithDet = [];
        foreach ($dataHasil as $key => $value) {
            $hasilFIFO = 0;
            if ($withFifo) {
                $dataFIFO = $this->fifoUpdateDLV($value['DEL_DATE'], '', false, true);
                foreach ($dataFIFO as $keyFIFO => $valueFIFO) {
                    $hasilFIFO += $valueFIFO['DRD_QTY'];
                }
            }
            
            $dataWithDet[] = array_merge(
                ['no' => $key + 1],
                $value,
                [
                    'TOTAL_FIFO' => $hasilFIFO
                ]
            );
        }

        return $dataWithDet;
    }

    public function DLVWithBarcode(Request $req)
    {
        ini_set('max_execution_time', '300');
        $hasil = [];
        foreach ($req->model as $key => $value) {
            $date_to = date('d', strtotime($req->date)) == 1 ? date('Y-m-d') : date('Y-m-d', strtotime($req->date . "-1 days"));
            $query = "SET NOCOUNT ON;EXEC Z_STXI_GET_CPO_DLV_STXI_ITEC @model = '" . $value . "', @date_start = '" . date('Y-m-01', strtotime($req->date)) . "', @date_to = '" . $date_to . "'";

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
                ? (!$getSPQDataPersheet || (int)$req->delivery[$key] < (int)$getSPQDataPersheet || ((int)($req->delivery[$key] / (int)$getSPQDataPersheet) !== ($req->delivery[$key] / (int)$getSPQDataPersheet))
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

    public function deleteDelivery($date, $loc = 'smt')
    {
        $hasil = DLVTYOHist::where('DEL_DATE', $date);

        if ($loc === 'smt') {
            $hasil->whereIn('IO_REMARK', ['TO_ITEC', 'FROM_SMT'])->delete();
        } else {
            $hasil->whereIn('IO_REMARK', ['TO_ITEC_STOCKDLV', 'FROM_STOCK'])->delete();
        }

        return $this->handleResponse($hasil, 'Data delivery on ' . $date . ' deleted !');
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
                DLVTYOHist::where('DEL_DATE', $req->date)->where('MITM_MODELCD', $value['model'])->where('IO_REMARK', $req->dlvStoc ? 'TO_ITEC_STOCKDLV' : 'TO_ITEC')->delete();
                $hasil[] = DLVTYOHist::create([
                    'MITM_MODELCD' => $value['model'],
                    'IO_QTY' => $value['withBarcode'] * -1,
                    'IO_REMARK' => $req->dlvStoc ? 'TO_ITEC_STOCKDLV' : 'TO_ITEC',
                    'IPP_REMARK' => $value['ipp'],
                    'RANK_REMARK' => $value['rank'],
                    'DEL_DATE' => $req->date,
                ]);
            }

            if (!empty($value['delivery'])) {
                DLVTYOHist::where('DEL_DATE', $req->date)->where('MITM_MODELCD', $value['model'])->where('IO_REMARK', $req->dlvStoc ? 'FROM_STOCK' : 'FROM_SMT')->delete();
                $hasil[] = DLVTYOHist::create([
                    'MITM_MODELCD' => $value['model'],
                    'IO_QTY' => $value['delivery'],
                    'IO_REMARK' => $req->dlvStoc ? 'FROM_STOCK' : 'FROM_SMT',
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

            if (!$req->dlvStoc) {
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

        $date_to = date('d', strtotime($date)) == 1 ? date('Y-m-d') : date('Y-m-d', strtotime($date . "-1 days"));
        if (!empty($item)) {
            $query = "SET NOCOUNT ON;EXEC Z_STXI_GET_CPO_DLV_STXI_ITEC @date_start = '" . date('Y-m-01', strtotime($date)) . "', @date_to = '" . $date_to . "', @model = '" . $item . "'";
        } else {
            $query = "SET NOCOUNT ON;EXEC Z_STXI_GET_CPO_DLV_STXI_ITEC @date_start = '" . date('Y-m-01', strtotime($date)) . "', @date_to = '" . $date_to . "'";
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

    public function fifoUpdateDLV($date = null, $item = '', $isSave = false, $byItemOnly = false)
    {
        $data = $this->DLVGetData($date, [
            'MITM_MODELCD',
            'MITM_ITMD1',
            'DEL_DATE'
        ], false, true, $isSave, $item);

        // return $data;

        $hasil = [];
        foreach ($data as $key => $value) {
            if ($value['TOT_OUT_BC_DLV'] > 0 || $value['TOT_OUT_STOCK_DLV'] > 0) {
                // $ttlDlv = $value['TOT_OUT_BC_DLV'] + $value['TOT_OUT_STOCK_DLV'];
                $getID = DLVTYOHist::select('id', 'IO_REMARK')
                    ->where('MITM_MODELCD', $value['MITM_MODELCD'])
                    ->where('DEL_DATE', $value['DEL_DATE'])
                    ->whereIn('IO_REMARK', ['TO_ITEC', 'TO_ITEC_STOCKDLV'])
                    ->get()
                    ->toArray();

                // return $getID;

                // if ($value['MITM_MODELCD'] == 'F65929-09V') {
                //     return $getID;
                // }

                if (!$byItemOnly) {
                    $hasil[$value['MITM_MODELCD']]['MODELCD'] = $value['MITM_MODELCD'];
                    $hasil[$value['MITM_MODELCD']]['MODELDESC'] = $value['MITM_ITMD1'];
                }
                foreach ($getID as $keyID => $valueID) {
                    if ($isSave) {
                        if ($valueID['IO_REMARK'] == 'TO_ITEC') {
                            $valFifo = "'" . $value['MITM_MODELCD'] . "', " . $value['TOT_OUT_BC_DLV'] . ", '" . date('Y-m-01', strtotime($date)) . "'";
                            $hasil[$value['MITM_MODELCD']]['DATA_DATE'][$value['DEL_DATE']][$keyID]['DLVQT'] = $value['TOT_OUT_BC_DLV'];
                        } else {
                            $valFifo = "'" . $value['MITM_MODELCD'] . "', " . $value['TOT_OUT_STOCK_DLV'] . ", '" . date('Y-m-01', strtotime($date)) . "'";
                            $hasil[$value['MITM_MODELCD']]['DATA_DATE'][$value['DEL_DATE']][$keyID]['DLVQT'] = $value['TOT_OUT_STOCK_DLV'];
                        }

                        $dataFIfo = DB::connection('sqlsrv_mega_tyo')
                            ->table("Z_STXI_FIFO_OS_SO(" . $valFifo . ")")
                            ->get()
                            ->toArray();

                        DLVTYODet::where('DRST_ID', $valueID['id'])->delete();

                        $statInsert = [];
                        foreach ($dataFIfo as $keyInsert => $valueInsert) {
                            $statInsert[] = DLVTYODet::create([
                                'DRST_ID' => (int)$valueID['id'],
                                'DRD_DELNO' => (string)$valueInsert->SSO2_DELNO,
                                'DRD_PRICE' => round($valueInsert->SSO2_SLPRC, 2),
                                'DRD_QTY' => (int)$valueInsert->USED_QT,
                                'DRD_DELDT' => $valueInsert->SSO2_DELDT,
                            ]);
                        }

                        $hasil[$value['MITM_MODELCD']]['DATA_DATE'][$value['DEL_DATE']][$keyID]['ID_HIST'] = $valueID['id'];
                        $hasil[$value['MITM_MODELCD']]['DATA_DATE'][$value['DEL_DATE']][$keyID]['FIFO_DATA'] = $statInsert;
                        // $hasil[$value['MITM_MODELCD']]['DATA_DATE'][$value['DEL_DATE']][$keyID]['FIFO_DATA_TEST'] = $dataFIfo;
                    } else {
                        if ($byItemOnly) {
                            $statInsert = DLVTYODet::select(
                                'MITM_MODELCD',
                                'IO_REMARK',
                                'DRD_DELNO',
                                'DRD_PRICE',
                                'DRD_QTY',
                                'DRD_DELDT'
                            )->where('DRST_ID', $valueID['id'])
                            ->join('DLV_REQ_SMT_TYO', 'DLV_REQ_SMT_TYO.id', 'DRST_ID')
                            ->get()
                            ->toArray();
                            $hasil = array_merge($hasil, $statInsert);
                            // array_push($hasil, $statInsert);
                        } else {
                            if ($valueID['IO_REMARK'] == 'TO_ITEC') {
                                $hasil[$value['MITM_MODELCD']]['BC_DLV']['TOTAL'] = $value['TOT_OUT_BC_DLV'];
                                $statInsert = DLVTYODet::where('DRST_ID', $valueID['id'])->get()->toArray();

                                $hasil[$value['MITM_MODELCD']]['BC_DLV']['ID_HIST'] = $valueID['id'];
                                $hasil[$value['MITM_MODELCD']]['BC_DLV']['FIFO_DATA'] = $statInsert;
                            } else {
                                $hasil[$value['MITM_MODELCD']]['STOCK_DLV']['TOTAL'] = $value['TOT_OUT_STOCK_DLV'];
                                $statInsert = DLVTYODet::where('DRST_ID', $valueID['id'])->get()->toArray();

                                $hasil[$value['MITM_MODELCD']]['STOCK_DLV']['ID_HIST'] = $valueID['id'];
                                $hasil[$value['MITM_MODELCD']]['STOCK_DLV']['FIFO_DATA'] = $statInsert;
                            }
                        }
                    }
                }
            }
        }

        return array_values($hasil);
    }

    public function calculateFIFO($data, $qty, $hasil = [])
    {
        $nowData = current($data);
        // return $nowData;
        if (isset($nowData['FIFO_QT'])) {
            $total = $nowData['FIFO_QT'] - $qty;

            if ($total > 0) {
                $hasil[] = $nowData;
            } else {
                next($data);
                $hasil[] = $this->calculateFIFO($data, $total);
            }
        }

        return $hasil;
    }
}
