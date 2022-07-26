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

    public function SPQIndex()
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
            // ->where('MITM_MODEL', 1)
            ->get();


        return $this->handleResponse($data, 'Data found !');
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
        return $this->handleResponse($this->DLVGetData(), 'Data found !');
    }

    public function DLVGetData(
        $date = null,
        $sel = [
            'MITM_MODELCD',
            'MITM_ITMD1',
            'DEL_DATE'
        ]
    ) {
        $data = DB::connection('sqlsrv_ems2')->table('V_DLV_TYO_HIST')->select(
            array_merge($sel, [
                DB::raw('SUM(I_QTY) AS TOT_INC_DLV'),
                DB::raw('SUM(O_QTY) AS TOT_OUT_BC_DLV'),
                DB::raw('SUM(OWB_QTY) AS TOT_OUT_WOBC_DLV'),
                DB::raw('SUM(TOT_QTY) AS TOT_SMT_DLV')
            ])
        )->join(
            DB::raw('[MGSVR].[VMI_TYO].[dbo].[MITM_TBL]'),
            'MITM_ITMCD',
            'MITM_MODELCD'
        )
            ->groupBy($sel);

        if (!empty($date)) {
            $data->where('DEL_DATE', $date);
        }

        return array_map(function ($value) {
            return (array)$value;
        }, $data->get()->toArray());
    }

    public function DLVWithBarcode(Request $req)
    {
        $hasil = [];
        foreach ($req->model as $key => $value) {
            $query = "SET NOCOUNT ON;EXEC Z_STXI_GET_CPO_DLV_STXI_ITEC @model = '" . $value . "', @date_start = '" . date('Y-m-01', strtotime($req->date)) . "', @date_to = '" . date('Y-m-d', strtotime($req->date . "-1 days")) . "'";
            $dataCPO = collect(DB::connection('sqlsrv_mega_tyo')->select(DB::raw(
                $query
            )))[0];

            $hasilWithBarcode = $req->delivery[$key] > $dataCPO->BAL_CPO_STXI_ITEC ? (int)$dataCPO->BAL_CPO_STXI_ITEC : $req->delivery[$key];
            $getSPQArray = $this->DLVCalSPQRes($hasilWithBarcode, $req->delivery[$key], $value);

            $hasil[] = [
                // 'query' => $query,
                'model' => $value,
                'delivery' => $req->delivery[$key],
                'cpo' => (int)$dataCPO->BAL_CPO_STXI_ITEC,
                'withBarcode' => $hasilWithBarcode > 0 ? $hasilWithBarcode : $req->delivery[$key],
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
        // return [$delivery . ' X ' . 1];
        $getSPQData = SPQMaster::where('MITM_MODELCD', $model)->first();
        if ($qty <= 0 || empty($getSPQData)) {
            return [$delivery . ' X ' . 1];
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
        return 'Email sent !!';
    }
}
