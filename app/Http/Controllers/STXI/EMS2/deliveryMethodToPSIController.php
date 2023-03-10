<?php

namespace App\Http\Controllers\STXI\EMS2;

use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\STXI\importSPQMaster;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\File;

use App\Models\STXI\EMS2\SPQMaster;
use App\Models\STXI\EMS2\DLVTYOHist;
use App\Models\STXI\EMS2\DLVTYODet;
use App\Models\STXI\EMS2\DLVTYODlvDet;
use App\Models\STXI\EMS2\DLVTYOWkRpt;
use App\Models\STXI\EMS2\TYO_PO_MSTR;

use App\Jobs\STXI\EMS2\DLVSMTTYOEmailQueue;
use App\Exports\STXT\exportDeliveryHist;
use App\Exports\STXI\ExportDODelivery;
use App\Exports\STXI\ExportDOWeeklyReport;
use App\Exports\STXI\ExportDOMegaUpload;
use App\Exports\STXI\ExportDOChecker;

use App\Imports\STXI\importWeeklyReport;
use App\Imports\STXI\EMS2\ImportPOWebEDITYO;
use App\Imports\STXI\EMS2\ImportFIFODOTYO;

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
            ->whereIn('MITM_MODEL', [0, 1]);

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
        ini_set('max_execution_time', '300');
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
        $withFifo = false,
        $withTransID = false
    )
    {
        $selHeader = array_merge($sel);

        if (!empty($date)) {
            $selHeader = array_merge(
                $selHeader,
                [
                    DB::raw('DLV_REQ_TYO_DET.DRT_TRANID')
                ]
            );
        }

        $data = DB::connection('sqlsrv_ems2')->table('V_DLV_TYO_HIST')->select(
            array_merge(
                $selHeader,
                [
                    DB::raw('SUM(I_QTY) AS TOT_INC_DLV'),
                    DB::raw('SUM(IS_QTY) AS TOT_INC_STOCK_DLV'),
                    DB::raw('SUM(O_QTY) AS TOT_OUT_BC_DLV'),
                    DB::raw('SUM(OWB_QTY) AS TOT_OUT_WOBC_DLV'),
                    DB::raw('SUM(TOT_QTY) AS TOT_SMT_DLV'),
                    DB::raw('SUM(OQS_QTY) AS TOT_OUT_STOCK_DLV'),
                    DB::raw('(SUM(O_QTY) + SUM(OWB_QTY)) + SUM(OQS_QTY) AS TOT_OUT'),
                    DB::raw('MAX(IPP_REMARK) AS IPP_REMARK'),
                    DB::raw('MAX(RANK_REMARK) AS RANK_REMARK'),
                    DB::raw('COUNT(DLV_REQ_TYO_DET.id) AS STORED_ITEM_DET')
                ]
            )

        )->join(
                DB::raw('[MGSVR].[VMI_TYO].[dbo].[MITM_TBL]'),
                'MITM_ITMCD',
                'MITM_MODELCD'
            )->leftjoin('DLV_REQ_TYO_DET', function ($j) {
                $j->on('DRT_ITMCD', 'MITM_MODELCD');
                $j->on('DRT_PSI_DELDT', 'DEL_DATE');
            })
            ->groupBy($selHeader)
            ->orderBy('DEL_DATE', 'DESC');

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
            return (array) $value;
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
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', '300');
        $hasil = [];
        foreach ($req->model as $key => $value) {
            $date_to = date('d', strtotime($req->date)) == 1 ? date('Y-m-d') : date('Y-m-d', strtotime($req->date . "-1 days"));
            $query = "SET NOCOUNT ON;EXEC Z_STXI_GET_CPO_DLV_STXI_ITEC @model = '" . $value . "', @date_start = '" . date('Y-m-01', strtotime($req->date)) . "', @date_to = '" . $date_to . "'";

            $dataCPO = collect(
                DB::connection('sqlsrv_mega_tyo')->select(
                    DB::raw(
                        $query
                    )
                )
            )[0];

            $getSPQDataPersheet = $this->SPQIndex($value)->original['data'] ? $this->SPQIndex($value)->original['data']['MITM_SPQ_CHECK'] : false;

            $hasilWithBarcode = (int) $dataCPO->BAL_CPO_STXI_ITEC > 0
                ? (!$getSPQDataPersheet || (int) $req->delivery[$key] < (int) $getSPQDataPersheet
                    ? 0
                    : ($req->delivery[$key] > (int) $dataCPO->BAL_CPO_STXI_ITEC && $req->delivery[$key] > (int) $getSPQDataPersheet
                        ? (int) $dataCPO->BAL_CPO_STXI_ITEC
                        : $req->delivery[$key]
                    )
                )
                : 0;

            // $hasilWithBarcode = (int)$dataCPO->BAL_CPO_STXI_ITEC > 0
            //     ? (!$getSPQDataPersheet || (int)$req->delivery[$key] < (int)$getSPQDataPersheet || ((int)($req->delivery[$key] / (int)$getSPQDataPersheet) !== ($req->delivery[$key] / (int)$getSPQDataPersheet))
            //         ? 0
            //         : ($req->delivery[$key] > (int)$dataCPO->BAL_CPO_STXI_ITEC && $req->delivery[$key] > (int)$getSPQDataPersheet
            //             ? (int)$dataCPO->BAL_CPO_STXI_ITEC
            //             : $req->delivery[$key]
            //         )
            //     )
            //     : 0;

            // if ($value === 'F41584-06') {
            //     return $this->DLVCalSPQRes($hasilWithBarcode, $req->delivery[$key], $value);
            // }
            $getSPQArray = $hasilWithBarcode > 0 ? $this->DLVCalSPQRes($hasilWithBarcode, $req->delivery[$key], $value) : 0;

            // return $getSPQArray;
            $hasil[] = [
                // 'query' => $query,
                'model' => $value,
                'delivery' => $req->delivery[$key],
                'cpo' => (int) $dataCPO->BAL_CPO_STXI_ITEC,
                'withBarcode' => $hasilWithBarcode,
                'spq' => $getSPQArray
            ];
        }

        return $this->handleResponse($hasil, 'Data found !');
    }

    public function deleteDelivery($date, $loc = 'smt', $item = '')
    {
        $q = DLVTYOHist::where('DEL_DATE', $date);

        if ($loc === 'smt') {
            $q->whereIn('IO_REMARK', ['TO_ITEC', 'FROM_SMT'])->get();
        } else {
            $q->whereIn('IO_REMARK', ['TO_ITEC_STOCKDLV', 'FROM_STOCK'])->get();
        }

        if (!empty($item)) {
            $q->where('MITM_MODELCD', $item);
        }

        $hasil = $q->get();

        foreach ($hasil as $key => $value) {
            DLVTYODet::where('DRST_ID', $value->id)->delete();
            DLVTYODlvDet::where('DRT_ITMCD', $value->MITM_MODELCD)->where('DRT_PSI_DELDT', $value->DEL_DATE)->delete();
        }

        $q->delete();

        return $this->handleResponse($hasil, 'Data delivery on ' . $date . ' deleted !');
    }

    public function DLVCalcSPQ($qty, $spq, $hasil = [])
    {
        if ($spq > 0) {
            if ($qty > $spq) {
                $total = $qty - $spq;
                $hasil[] = $spq;

                return $this->DLVCalcSPQ($total, $spq, $hasil);
            } else {
                $total = $qty;
                $hasil[] = $total;

                return $hasil;
            }
        } else {
            $total = $qty;
            $hasil[] = $total;

            return $hasil;
        }
    }

    public function DLVCalSPQRes($qty, $delivery, $model, $qtyArray = false)
    {
        $getSPQData = SPQMaster::where('MITM_MODELCD', $model)->first();
        if ($qty <= 0 || empty($getSPQData)) {
            return "0";
        }

        // return [$qty, isset($getSPQData->STXI_SPQ) ? (int)$getSPQData->STXI_SPQ : 0];
        $getSPQArray = $this->DLVCalcSPQ($qty, isset($getSPQData->STXI_SPQ) ? (int) $getSPQData->STXI_SPQ : 0);
        // return $getSPQArray;

        $hasilSPQ = [];
        $totalBox = 1;
        $data = -1;

        foreach ($getSPQArray as $keySPQArr => $valueSPQArr) {
            if (!$qtyArray) {
                if ($keySPQArr > 0 && $valueSPQArr === $getSPQArray[$keySPQArr - 1]) {
                    $totalBox = $totalBox + 1;

                    $hasilSPQ[$data] = $valueSPQArr . ' X ' . $totalBox;
                } else {
                    $data++;
                    $totalBox = 1;
                    $hasilSPQ[$data] = $valueSPQArr . ' X ' . $totalBox;
                }
            } else {
                $data++;
                $totalBox = 1;
                $hasilSPQ[$data] = $valueSPQArr;
            }
        }

        return $hasilSPQ;
    }

    public function DLVStore(Request $req)
    {
        ini_set('max_execution_time', '300');
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

                $this->fifoUpdateDLV($req->date, $value['model'], true, false);
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

        $insertJob = (
            new DLVSMTTYOEmailQueue(
                'PT SMT Indonesia',
                $hasilData,
                $totalDelivery,
                $totalWBarcode,
                $totalWOBarcode,
                $totalSMTDlv,
                $date
            )
        );

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
        ini_set('max_execution_time', '600');

        $date_to = (int) date('d', strtotime($date)) == 1 ? date('Y-m-d', strtotime($date . "-1 days")) : date('Y-m-d');
        if (!empty($item)) {
            $query = "SET NOCOUNT ON;EXEC Z_STXI_GET_CPO_DLV_STXI_ITEC @date_start = '" . date('Y-m-01', strtotime($date)) . "', @date_to = '" . $date . "', @model = '" . $item . "'";
        } else {
            $query = "SET NOCOUNT ON;EXEC Z_STXI_GET_CPO_DLV_STXI_ITEC @date_start = '" . date('Y-m-01', strtotime($date)) . "', @date_to = '" . $date . "'";
        }

        // return $query;

        $dataCPO = collect(
            DB::connection('sqlsrv_mega_tyo')->select(
                DB::raw(
                    $query
                )
            )
        );

        $getCPO = $dataCPO->where('BAL_STOCK', '>', 0)
            ->where('BAL_CPO_STXI_ITEC', '>', 0)
            ->map(function ($t) {
                return collect($t)->only([
                    'MITM_ITMCD',
                    'MITM_ITMD1',
                    'BAL_CPO_STXI_ITEC',
                    'BAL_STOCK'
                ]);
            })->toArray();

        return array_values($getCPO);
    }

    public function fifoUpdateDLV($date = null, $item = '', $isSave = false, $byItemOnly = false, $dateFifoStart = 0, $do = 0, $qty = 0)
    {
        $data = $this->DLVGetData($date, [
            'MITM_MODELCD',
            'MITM_ITMD1',
            'DEL_DATE',
            'DRT_TRANID',
            'DRT_DELDT'
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
                        DLVTYODet::where('DRST_ID', $valueID['id'])->forceDelete();
                        if ($valueID['IO_REMARK'] == 'TO_ITEC') {
                            $valFifo = "'" . $value['MITM_MODELCD'] . "', " . $value['TOT_OUT_BC_DLV'] . ", '" . date($dateFifoStart === 0 ? 'Y-m-01' : 'Y-m-d', $dateFifoStart === 0 ? strtotime('-1 month', strtotime($date)) : strtotime($dateFifoStart)) . "', '" . date('Y-m-01', strtotime($date)) . "', '" . ($do == 0 ? '' : $do) . "'";
                            $hasil[$value['MITM_MODELCD']]['DATA_DATE'][$value['DEL_DATE']][$keyID]['DLVQT'] = $value['TOT_OUT_BC_DLV'];
                            $hasil[$value['MITM_MODELCD']]['DATA_DATE'][$value['DEL_DATE']][$keyID]['CEK'] = $valFifo;
                        } else {
                            $valFifo = "'" . $value['MITM_MODELCD'] . "', " . $value['TOT_OUT_STOCK_DLV'] . ", '" . date($dateFifoStart === 0 ? 'Y-m-01' : 'Y-m-d', $dateFifoStart === 0 ? strtotime('-1 month', strtotime($date)) : strtotime($dateFifoStart)) . "', '" . date('Y-m-01', strtotime($date)) . "', '" . ($do === 0 ? '' : $do) . "'";
                            $hasil[$value['MITM_MODELCD']]['DATA_DATE'][$value['DEL_DATE']][$keyID]['DLVQT'] = $value['TOT_OUT_STOCK_DLV'];
                            $hasil[$value['MITM_MODELCD']]['DATA_DATE'][$value['DEL_DATE']][$keyID]['CEK'] = $valFifo;
                        }

                        $dataFIfo = DB::connection('sqlsrv_mega_tyo')
                            ->table("Z_STXI_FIFO_OS_SO(" . $valFifo . ")")
                            ->get()
                            ->toArray();

                        $statInsert = [];
                        foreach ($dataFIfo as $keyInsert => $valueInsert) {
                            $statInsert[] = DLVTYODet::create([
                                'DRST_ID' => (int) $valueID['id'],
                                'DRD_DELNO' => (string) $valueInsert->SSO2_DELNO,
                                'DRD_PRICE' => round($valueInsert->SSO2_SLPRC, 2),
                                'DRD_QTY' => (int) $valueInsert->USED_QT,
                                'DRD_DELDT' => $valueInsert->SSO2_DELDT,
                            ]);
                        }

                        $hasil[$value['MITM_MODELCD']]['DATA_DATE'][$value['DEL_DATE']][$keyID]['ID_HIST'] = $valueID['id'];
                        $hasil[$value['MITM_MODELCD']]['DATA_DATE'][$value['DEL_DATE']][$keyID]['FIFO_DATA'] = $statInsert;
                        $hasil[$value['MITM_MODELCD']]['DATA_DATE'][$value['DEL_DATE']][$keyID]['FIFO_QUERY'] = "Z_STXI_FIFO_OS_SO(" . $valFifo . ")";
                        // $hasil[$value['MITM_MODELCD']]['DATA_DATE'][$value['DEL_DATE']][$keyID]['FIFO_DATA_TEST'] = $dataFIfo;
                    } else {
                        if ($byItemOnly) {
                            $statInsert = DLVTYODet::select(
                                'DLV_REQ_DET.id',
                                'MITM_MODELCD',
                                'IO_REMARK',
                                'DRD_DELNO',
                                'DRD_PRICE',
                                'DRD_QTY',
                                'DRD_DELDT',
                                'DRT_TRANID',
                                'DRT_DELDT'
                            )->where('DRST_ID', $valueID['id'])
                                ->join('DLV_REQ_SMT_TYO', 'DLV_REQ_SMT_TYO.id', 'DRST_ID')
                                ->leftjoin('DLV_REQ_TYO_DET', function ($j) {
                                    $j->on('DRT_ITMCD', 'MITM_MODELCD');
                                    $j->on('DRT_PSI_DELDT', 'DEL_DATE');
                                })
                                ->get()
                                ->toArray();
                            $hasil = array_merge($hasil, $statInsert);
                            // array_push($hasil, $statInsert);
                        } else {
                            $valFifo3 = "'" . $value['MITM_MODELCD'] . "', " . ($qty === 0 ? ($value['TOT_OUT_BC_DLV'] + $value['TOT_OUT_STOCK_DLV']) : $qty) . ", '" . date($dateFifoStart === 0 ? 'Y-m-01' : 'Y-m-d', $dateFifoStart === 0 ? strtotime('-1 month', strtotime($date)) : strtotime($dateFifoStart)) . "', '" . date('Y-m-01', strtotime($date)) . "', '" . ($do == 0 ? '' : $do) . "'";
                            $checkFIFO = DB::connection('sqlsrv_mega_tyo')
                                ->table("Z_STXI_FIFO_OS_SO(" . $valFifo3 . ")")
                                ->get()
                                ->toArray();

                            $hasilFifo = [];
                            foreach ($checkFIFO as $key => $valueFif) {
                                $hasilFifo[] = array_merge(
                                    (array) $valueFif,
                                    [
                                        'ID_CUST' => trim($valueFif->SSO2_DELNO) . '-' . date('y-m-d', strtotime($valueFif->SSO2_ISUDT)) . '-' . $valueFif->SSO2_SLPRC
                                    ]
                                );
                            }

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

                            $hasil[$value['MITM_MODELCD']]['FIFO_LIST'] = $hasilFifo;
                            $hasil[$value['MITM_MODELCD']]['FIFO_QUERY'] = "Z_STXI_FIFO_OS_SO(" . $valFifo3 . ")";
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

    public function exportDOExcel($date, $item = '')
    {
        ini_set('memory_limit', '5G');
        $data = $this->DLVGetData($date, [
            'MITM_MODELCD',
            'MITM_ITMD1',
            'DEL_DATE',
            'IPP_REMARK',
            'RANK_REMARK'
        ], !empty($date), true, false, $item, true);

        // return $data;

        $hasilData = [];
        $totalDelivery = 0;
        $totalWBarcode = 0;
        $totalWOBarcode = 0;
        $totalSMTDlv = 0;

        $countMax = 0;
        foreach ($data as $key => $value) {
            $fifoUpdate = [];
            foreach ($this->fifoUpdateDLV($date, $value['MITM_MODELCD'], false, true) as $keyFIFO => $valueFIFO) {
                $fifoUpdate[] = array_merge(
                    $valueFIFO,
                    // [
                    //     'SPQ' => $this->DLVCalSPQRes($valueFIFO['DRD_QTY'], 0,$value['MITM_MODELCD'], true)
                    // ]
                );
            }
            $getDataSPQ = SPQMaster::where('MITM_MODELCD', $value['MITM_MODELCD'])->first();
            $getSPQFetTest = $this->newFIFOSPQ3(
                $fifoUpdate,
                $this->SPQIndex($value['MITM_MODELCD'])->original['data']['MITM_SPQ_CHECK'],
                $value['TOT_OUT_BC_DLV'] + $value['TOT_OUT_STOCK_DLV']
            );

            $hasilSPQ = [];
            foreach ($getSPQFetTest as $keySPQ => $valueSPQ) {
                $hasilSPQ[$valueSPQ['DRD_DELNO'] . '-' . $valueSPQ['DRD_QTY']][] = $valueSPQ;
            }

            $hasilFinalSPQ = [];
            foreach ($hasilSPQ as $keyDetSPQ => $valueSPQ) {
                $countBox = $sumTotal = 0;
                foreach ($valueSPQ as $keyDetDeepSPQ => $valueDeepSPQ) {
                    $countBox++;
                }

                $hasilFinalSPQ[] = array_merge(
                    $valueSPQ[0],
                    [
                        'BOX_COUNT' => $countBox
                    ]
                );
            }

            $countMax = count($getSPQFetTest) > $countMax ? count($getSPQFetTest) : $countMax;

            $totalDelivery += $value['TOT_INC_DLV'];
            $totalWBarcode += $value['TOT_OUT_BC_DLV'];
            $totalWOBarcode += $value['TOT_OUT_WOBC_DLV'];
            $totalSMTDlv += $value['TOT_SMT_DLV'];
            $hasilData[] = array_merge(
                $value,
                [
                    'FIFO_DET' => $fifoUpdate,
                    'SPQ_FET' => array_values($hasilFinalSPQ),
                    // 'SPQ_FET' => $hasilFinalSPQ,
                    // 'TEST_SPQ' => $getSPQFet
                    // 'SPQ_DATA' => $this->SPQIndex($value['MITM_MODELCD'])->original['data']
                ]
            );
        }

        // return $hasilData;

        Excel::store(new ExportDODelivery($hasilData, $date), 'export_fifo_delivery_' . $date . '.xlsx', 'public');

        return 'storage/app/public/export_fifo_delivery_' . $date . '.xlsx';
    }

    public function newFIFOSPQ3($data, $spq, $qtyDlv, $barcodeInt = 0, $hasil = [], $dataBefore = null)
    {
        $nowData = current($data);

        if ($nowData) {
            $totalSPQ = $cekSisaDRD = $cekSisaSPQ = $totalDRD = $tempQty = $finalQty = $totalAll = 0;
            if (empty($dataBefore)) {
                $barcodeInt = $barcodeInt + 1;
                // Jika SPQ > dari pada DN Qty
                if ($spq > $nowData['DRD_QTY']) {
                    $tempQty = $nowData['DRD_QTY'];
                    $totalSPQ = $tempQty;
                } else {
                    $tempQty = $spq;
                    $totalDRD = $tempQty;
                }

                $totalAll = $tempQty;
            } else {
                $cekSisaSPQ = $spq - (int) $dataBefore['SUM_SPQ_QTY'];
                $cekSisaDRD = (int) $nowData['DRD_QTY'] - (int) $dataBefore['SUM_DRD_QTY'];

                // 1800 > 360
                if ($cekSisaSPQ > $cekSisaDRD) {
                    $cekSisaDRDSubstrWithNow = $cekSisaDRD - (int) $nowData['DRD_QTY'];
                    // 1080 - 1080
                    if ($cekSisaDRDSubstrWithNow >= 0) {
                        if ($cekSisaSPQ > (int) $nowData['DRD_QTY']) {
                            $totalSPQ = (int) $dataBefore['SUM_SPQ_QTY'] + (int) $nowData['DRD_QTY'];
                            $tempQty = (int) $nowData['DRD_QTY'];
                        } else {
                            $tempQty = (int) $nowData['DRD_QTY'];
                        }
                        $totalDRD = $cekSisaDRDSubstrWithNow;
                    } else {
                        // $tempQty = (int)$nowData['DRD_QTY'] - $cekSisaDRD;
                        $tempQty = $cekSisaDRD;
                        $totalDRD = 0;
                        $totalSPQ = $cekSisaDRD;
                    }
                } else {
                    $totalSPQ = 0;
                    // $tempQty = $cekSisaSPQ;

                    $cekSisaDRDSubstrWithNowSPQ = $cekSisaDRD - $cekSisaSPQ;

                    if ($cekSisaDRDSubstrWithNowSPQ > 0) {
                        if ($cekSisaSPQ - $spq < 0) {
                            $tempQty = $cekSisaSPQ;
                            $totalDRD = (int) $tempQty;
                        } else {
                            $tempQty = $spq;
                            $totalDRD = (int) $dataBefore['SUM_DRD_QTY'] + $spq;
                        }
                    } elseif ($cekSisaDRDSubstrWithNowSPQ === 0) {
                        if ($cekSisaSPQ < $spq) {
                            $tempQty = $cekSisaSPQ;
                        } else {
                            $tempQty = $spq;
                        }
                        $totalDRD = 0;
                    } else {
                        $tempQty = $cekSisaDRD;
                        $totalDRD = 0;
                    }
                }

                $totalAll = $tempQty + $dataBefore['TOTAL'];
            }

            if (!empty($dataBefore) && $dataBefore['SUM_SPQ_QTY'] == 0) {
                $barcodeInt = $barcodeInt + 1;
            }

            // Jika total DN sebelumnya + dn sekarang sama dengan data DN sekarang
            if (($totalDRD) === 0) {
                next($data);
            }

            // if (count($hasil) > 5) {
            //     next($data);
            // }

            // Jika SPQ belum di penuhi

            // Cek memenuhi DN Qty / tidak
            $finalQty = $tempQty;

            $insertData = array_merge(
                $nowData,
                [
                    'DRD_QTY' => $finalQty,
                    'SUM_DRD_QTY' => (int) $totalDRD,
                    'SUM_SPQ_QTY' => (int) $totalSPQ,
                    'TOTAL' => (int) $totalAll,
                    'BARCODE_REMARKS' => 'BARCODE-' . $barcodeInt,
                    'REAL_SPQ_QTY' => $spq,
                    'REAL_DRD_QTY' => (int) $nowData['DRD_QTY'],
                    // 'BOX_COUNT' => 1,
                    'CEK_DRD' => $cekSisaDRD,
                    'CEK_SPQ' => $cekSisaSPQ
                ]
            );

            if ($finalQty > 0) {
                $hasil[] = $insertData;
            }

            return $this->newFIFOSPQ3($data, $spq, $qtyDlv, $barcodeInt, $hasil, $insertData);
        } else {
            return $hasil;
        }
    }

    public function deliveryLatestNo($date)
    {
        $cekData = DLVTYODlvDet::orderBy('created_at', 'desc')
            ->where(DB::raw('MONTH(DRT_DELDT)'), date('m', strtotime($date)))
            ->where(DB::raw('YEAR(DRT_DELDT)'), date('Y', strtotime($date)))
            ->first();

        if (empty($cekData)) {
            return 'POT-' . date('ym', strtotime($date)) . '001';
        } else {
            return 'POT-' . date('ym', strtotime($date)) . sprintf('%03d', ((int) substr($cekData->DRT_TRANID, -3) + 1));
        }
    }

    public function deliveryToTYO(Request $req)
    {
        $hasil = [];
        foreach ($req->items as $key => $value) {
            $hasil[] = DLVTYODlvDet::create([
                'DRT_ITMCD' => $value,
                'DRT_PSI_DELDT' => $req->psideldt,
                'DRT_TRANID' => $req->tranid,
                'DRT_DELDT' => $req->deldt,
            ]);
        }

        return $this->handleResponse($hasil, 'Delivery note created !');
    }

    public function uploadFifoDOData(Request $req)
    {
        ini_set('max_execution_time', '300');
        // $nama_file = $req->file->hashName();
        $file = new File($req->file);
        $extNya = $req->file('file')->getClientOriginalExtension();

        $fileHash = str_replace('.' . $file->extension(), '', $file->hashName());
        $nama_file = $fileHash . '.' . $extNya;

        // return $nama_file;

        $req->file->storeAs('/public/upload_fifo_do_data/', $nama_file);

        if ($extNya == 'xls') {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $writer = new Xlsx($spreadsheet);
            $nama_file = $fileHash . '.xlsx';
            $writer->save('/public/upload_fifo_do_data/' . $nama_file);
        }

        // Delete exists DO by ID transaction
        $masterHist = DLVTYOHist::where('DEL_DATE', $req->date)->where('IO_REMARK', $req->typeTrans)->get();

        foreach ($masterHist as $key => $value) {
            DLVTYODet::where('DRST_ID', $value->id)->delete();
        }

        $importer = new ImportFIFODOTYO($req->date, $req->typeTrans);

        Excel::import($importer, public_path('/storage/upload_fifo_do_data/' . $nama_file));

        return $this->handleResponse($importer->getHasil(), 'Upload Sukses ' . $nama_file);
    }

    public function uploadWeeklyPOData(Request $req)
    {
        ini_set('max_execution_time', '300');
        // $nama_file = $req->file->hashName();
        $file = new File($req->file);
        $extNya = $req->file('file')->getClientOriginalExtension();

        $fileHash = str_replace('.' . $file->extension(), '', $file->hashName());
        $nama_file = $fileHash . '.' . $extNya;

        // return $nama_file;

        $req->file->storeAs('/public/upload_weekly_po_itec/', $nama_file);

        if ($extNya == 'xls') {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $writer = new Xlsx($spreadsheet);
            $nama_file = $fileHash . '.xlsx';
            $writer->save('/public/upload_weekly_po_itec/' . $nama_file);
        }


        $importer = new importWeeklyReport();

        Excel::import($importer, public_path('/storage/upload_weekly_po_itec/' . $nama_file));

        return $this->handleResponse([], 'Upload Sukses ' . $nama_file);
    }

    public function getUploadedWeeklyPO($date)
    {
        return $this->handleResponse(DB::connection('sqlsrv_ems2')->table("EMS2.dbo.f_itec_po_weekly_report('" . $date . "', '', '')")->get(), 'Data Found !!');
    }

    public function ExportWeeklyReport($date)
    {
        $data = DB::connection('sqlsrv_ems2')->table("EMS2.dbo.f_itec_po_weekly_report('" . $date . "', '', '')")->get()->transform(function ($i) {
            return (array) $i;
        })->toArray();

        Excel::store(new ExportDOWeeklyReport($data), 'export_weekly_PO_delivery_' . $date . '.xlsx', 'public');

        return 'storage/app/public/export_weekly_PO_delivery_' . $date . '.xlsx';
    }

    public function replaceFIFODO(Request $request)
    {
        $getID = DLVTYOHist::select('id', 'IO_REMARK')
            ->where('MITM_MODELCD', $request->item)
            ->where('DEL_DATE', $request->dlv_date)
            ->whereIn('IO_REMARK', $request->remark)
            ->get()
            ->toArray();

        $statInsert = [];
        foreach ($getID as $key => $value) {
            $delete = DLVTYODet::where('id', $value['id'])->delete();
            foreach ($request->data as $keyData => $valueData) {
                $statInsert[] = DLVTYODet::create([
                    'DRST_ID' => (int) $value['id'],
                    'DRD_DELNO' => (string) $valueData['SSO2_DELNO'],
                    'DRD_PRICE' => round($valueData['SSO2_SLPRC'], 2),
                    'DRD_QTY' => (int) $valueData['USED_QT'],
                    'DRD_DELDT' => $valueData['SSO2_DELDT'],
                ]);
            }
        }

        return $this->handleResponse($statInsert, 'Update FIFO Sukses !');
    }

    public function deleteFIFO($id)
    {
        $hasil = DLVTYODet::where('id', $id)->delete();
        return $this->handleResponse($hasil, 'Delete FIFO Sukses !');
    }

    // PO TYO Mega Upload

    public function uploadPO(Request $req)
    {
        ini_set('max_execution_time', '300');
        // $nama_file = $req->file->hashName();
        $file = new File($req->file);
        $extNya = $req->file('file')->getClientOriginalExtension();

        $fileHash = str_replace('.' . $file->extension(), '', $file->hashName());
        $nama_file = $fileHash . '.' . $extNya;

        // return $nama_file;

        $req->file->storeAs('/public/upload_raw_po_tyo/', $nama_file);

        if ($extNya == 'xls') {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $writer = new Xlsx($spreadsheet);
            $nama_file = $fileHash . '.xlsx';
            $writer->save('/public/upload_raw_po_tyo/' . $nama_file);
        }

        $importer = new ImportPOWebEDITYO();

        Excel::import($importer, public_path('/storage/upload_raw_po_tyo/' . $nama_file));

        return $this->handleResponse([], 'Upload Sukses ' . $nama_file);
    }

    public function getDataPOTYO(Request $req)
    {
        $data = TYO_PO_MSTR::select(
            'TYO_PO_MSTR.id as id',
            'TPM_ITMCD',
            'MITM_ITMCD',
            'TPM_ORDERNO',
            'TPM_STATUS',
            'TPM_ORDERQTY',
            'TPM_ORDER_CRTDT',
            'TPM_DLVDT',
            DB::raw('CASE WHEN PPO2_DELNO IS NULL
                THEN NULL
                ELSE PPO2_ISUDT
            END AS IS_POEXSTS
            '),
            DB::raw("CASE WHEN PPO2_DELNO IS NULL
                THEN 'New PO'
                ELSE 'Exists PO'
            END AS IS_POEXSTS_DESC
            ")
        )
        ->join(
            DB::raw('[MGSVR].[VMI_TYO].[dbo].[MITM_TBL]'),
            'MITM_ITMCD',
            'TPM_ITMCD'
        )
            ->leftjoin(
                DB::raw('[MGSVR].[VMI_TYO].[dbo].[PPO2_TBL]'),
                function ($j) {
                    $j->on('PPO2_MDLCD', 'TPM_ITMCD');
                    $j->on('PPO2_DELNO', 'TPM_ORDERNO');
                }
            )
            ->where('TPM_STATUS', 'New')
            ->whereNull('TPM_STOREID')
            ->orderBy('created_at');

        if ($req->has('cols')) {
            foreach ($req->cols as $key => $value) {
                if (!empty($value['filter'])) {
                    if ($value['eq'] === 'between') {
                        $data->whereBetween($value['name'], $value['filter']);
                    } else {
                        $data->where(
                            $value['type'] === 'date' || $value['type'] === 'datetime' && $value['eq'] === 'like'
                            ? DB::raw('CONVERT(VARCHAR(25), ' . $value['name'] . ', 126)')
                            : $value['name'],
                            $value['eq'],
                            $value['eq'] === 'like' ? $value['filter'] . '%' : $value['filter']
                        );
                    }

                }
            }
        }

        return $this->handleResponse($data->get(), 'Data Found !');
    }

    public function storeDraftPOTYO(Request $req)
    {
        $hasil = [];

        $cek = TYO_PO_MSTR::orderBy('id', 'desc')->where('TPM_STOREID', '<>', '')->first();
        $idStore = date('y/m/d') . '/' . (empty($cek) ? '0001' : sprintf('%04d', (int) substr($cek->TPM_STOREID, -3) + 1));
        foreach ($req->selected as $key => $value) {
            $hasil[] = TYO_PO_MSTR::where('id', $value['id'])->update([
                'TPM_STOREID' => $idStore,
                'TPM_ISSDT' => $req->issDate
            ]);
        }

        if (count($hasil) > 0) {
            return $this->handleResponse($hasil, count($req->selected) . ' Data Submited !');
        } else {
            return $this->handleError('Data Failed submit !');
        }
    }

    public function deleteDraftPOTYO(Request $req)
    {
        $hasil = [];

        foreach ($req->selected as $key => $value) {
            $hasil[] = TYO_PO_MSTR::where('id', $value['id'])->delete();
        }

        if (count($hasil) > 0) {
            return $this->handleResponse($hasil, count($req->selected) . ' Data Deleted !');
        } else {
            return $this->handleError('Data Failed to delete !');
        }
    }

    public function getPOTYOMegaReady($date, $isResponse = false)
    {
        $data = TYO_PO_MSTR::select(
            'TYO_PO_MSTR.id as id',
            'TPM_ITMCD',
            'MITM_ITMD1',
            'TPM_ISSDT',
            'TPM_DLVDT',
            'TPM_ORDERNO',
            'TPM_ORDERQTY',
            DB::raw('CAST(MITM_SPQ AS INT) AS SPQ'),
            DB::raw('CAST(TPM_ORDERQTY / (
                CASE WHEN SPQ_BOX_PROT_FLAG = 1
                    THEN STXI_SPQ
                    ELSE CAST(MITM_SPQ AS INT)
                END
            ) AS DECIMAL (15,2)) AS SHEET'),
            'MITM_RUNFG',
            'TPM_VERSION',
            'TPM_REMARK',
            DB::raw('
                ( TPM_ORDERQTY * (
                    SELECT TOP 1
                        MSPR_SLPRC
                    FROM[MGSVR].[VMI_TYO].[dbo].[MSPR_TBL] mtpr
                    WHERE mtpr.MSPR_ITMCD = TPM_ITMCD
                    ORDER BY MSPR_EFFDT DESC
                )) AS TPM_PRC
            '),
            DB::raw('
                DATEDIFF(day, TPM_ISSDT, TPM_DLVDT) as diff_days
            ')
        )->join(
            DB::raw('[MGSVR].[VMI_TYO].[dbo].[MITM_TBL]'),
            'MITM_ITMCD',
            'TPM_ITMCD'
        )->leftjoin(
            'SPQ_MSTR_TBL',
            'MITM_MODELCD',
            'TPM_ITMCD'
        )
            ->where('TPM_ISSDT', $date)
            ->whereNull('TPM_EXPORT');

        return !$isResponse ? $this->handleResponse($data->get(), 'Data Found !') : $data->get()->toArray();
    }

    public function UpdatePOTYOCells(Request $req)
    {
        $hasil = [];
        foreach ($req->selected as $key => $value) {
            $hasil[] = TYO_PO_MSTR::where('TPM_ORDERNO', $value['TPM_ORDERNO'])
                ->update([$req->column => $req->value]);
        }

        return $this->handleResponse($hasil, 'Data Updated !');
    }

    public function deleteToDraft(Request $req)
    {
        $hasil = [];
        foreach ($req->selected as $key => $value) {
            $hasil[] = TYO_PO_MSTR::where('TPM_ORDERNO', $value['TPM_ORDERNO'])
                ->update([
                    'TPM_STOREID' => NULL,
                    'TPM_ISSDT' => NULL
                ]);
        }

        return $this->handleResponse($hasil, 'Data Deleted !');
    }

    public function getAllRecordDateOnly()
    {
        $data = TYO_PO_MSTR::select('TPM_ISSDT')
            ->whereNotNull('TPM_STOREID')
            ->groupBy('TPM_ISSDT')
            ->whereNull('TPM_EXPORT')
            ->get()
            ->pluck('TPM_ISSDT');

        $hasil = [];
        foreach ($data as $key => $value) {
            $hasil[] = date('Y/m/d', strtotime($value));
        }

        return $this->handleResponse($hasil, 'Data Found !');
    }

    public function ExportTYODOMega(Request $request, $date)
    {
        $data = [];
        foreach ($request->data as $key => $value) {
            TYO_PO_MSTR::where('id', $value['id'])->update([
                'TPM_EXPORT' => 1
            ]);

            $data[] = TYO_PO_MSTR::where('id', $value['id'])->first();
        }

        // return $data;
        Excel::store(new ExportDOMegaUpload($data, $date), 'export_do_tyo_upload_mega.xlsx', 'public');

        return 'storage/app/public/export_do_tyo_upload_mega.xlsx';
    }

    public function ExportDOChecker($date)
    {
        $data = $this->getPOTYOMegaReady($date, true);

        // return $data;

        Excel::store(new ExportDOChecker($data), 'export_do_tyo_checker.xlsx', 'public');

        return 'storage/app/public/export_do_tyo_checker.xlsx';
    }
}