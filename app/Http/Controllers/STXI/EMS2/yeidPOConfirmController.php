<?php

namespace App\Http\Controllers\STXI\EMS2;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\API\PORTAL\BaseController;
use App\Models\STXI\EMS2\YPOMaster;
use App\Models\STXI\EMS2\YPOSTXIPODet;
use App\Exports\STXI\ExportYPOManual;
use Excel;
use Illuminate\Http\File;

use App\Jobs\STXI\EMS2\updateInvoiceYPOManualQueue;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Imports\STXI\EMS2\ImportSTXIYEIDPOConfirmation;

class yeidPOConfirmController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if ($request->has('data') && count($request->data) > 0) {
            $hasil = [];

            $getLastPO = YPOMaster::orderBy('created_at', 'desc')->first();

            $id = 'YPO-' . (empty($getLastPO) ? (date('y/m/d') . '/' . '0001') : date('y/m/d') . '/' . sprintf('%04d', (int) substr($getLastPO->YPO_TXID, -3) + 1));
            foreach ($request->data as $key => $value) {
                $hasil[] = YPOMaster::create([
                    'YPO_ITMCD' => $value['YPO_ITMCD'],
                    'YPO_REMARKS' => $value['YPO_REMARKS'],
                    'YPO_MRPDT' => $value['YPO_MRPDT'],
                    'YPO_MAILDT' => $value['YPO_MAILDT'],
                    'YPO_RCVDT' => $value['YPO_RCVDT'],
                    'YPO_PONO' => $value['YPO_PONO'],
                    'YPO_PODUEDT' => $value['YPO_PODUEDT'],
                    'YPO_POQTY' => $value['YPO_POQTY'],
                    'YPO_TXID' => $id,
                ]);
            }

            return $this->handleResponse($hasil, 'Add data inserted !');
        }

        return $this->handleError('Insert failed, not sending item !');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return $this->getDataYpo('', $id)->get();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = YPOMaster::where('YPO_TXID', base64_decode($id))
            ->where('YPO_ITMCD', $request->YPO_ITMCD)
            ->first();

        $det = [];
        foreach ($request->po as $key => $value) {
            $cekTotalNow = YPOSTXIPODet::select(DB::raw('SUM(YSPDT_POQT) QT'))->where('YMT_ID', $data->id)->first();
            $needQty = $data->YPO_POQTY - $cekTotalNow->QT;

            $det[] = YPOSTXIPODet::create([
                'YMT_ID' => $data->id,
                'YSPDT_PONO' => $value['PPO1_PONO'],
                'YSPDT_INVNO' => $value['PGIT_SUPNO'],
                'YSPDT_POQT' => (int) $value['PGIT_RCVQT'] >= $needQty ? $needQty : (int) $value['PGIT_RCVQT'],
                'YSPDT_POQTY' => (int)$value['PPO2_POQTY'],
            ]);
        }

        return $this->handleResponse([
            'master' => $data,
            'update' => $det
        ], 'Item updated !');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function searchItem($item, $col = '')
    {
        $data = DB::connection('sqlsrv_mega_exim')->table('MITM_TBL')->select(
            DB::raw('RTRIM(MITM_ITMCD) AS MITM_ITMCD'),
            DB::raw('RTRIM(MITM_ITMD1) AS MITM_ITMD1'),
            DB::raw('RTRIM(MITM_ITMD2) AS MITM_ITMD2'),
            DB::raw('RTRIM(MITM_STKUOM) AS MITM_STKUOM'),
            DB::raw('RTRIM(MITM_SUPCD) AS MITM_SUPCD'),
            DB::raw('RTRIM(MITM_SPTNO) AS MITM_SPTNO'),
            DB::raw('RTRIM(MSUP_ABBRV) AS MSUP_ABBRV'),
            DB::raw('RTRIM(MSUP_SUPNM) AS MSUP_SUPNM')
        )
            ->join('MSUP_TBL', 'MSUP_SUPCD', 'MITM_SUPCD')
            ->where('MITM_ITMCD', 'LIKE', base64_decode($item) . "%")
            // ->orwhere('MITM_ITMD1', 'LIKE', "'".base64_decode($item)."%'")
            ->get();

        // return $data;
        if (count($data) > 0) {
            if (!empty($col)) {
                $data = $data->pluck($col);
            }

            return $this->handleResponse($data, 'Item found !');
        }

        return $this->handleError('No item found !');
    }

    public function searchPO($item, $po = '', $col = 'PPO1_PONO')
    {
        $data = DB::connection('sqlsrv_ems2')->table('V_YPO_OS_GIT')
            ->where('PPO2_ITMCD', base64_decode($item));
            // ->whereNull('PGRN_RCVDT')
            // ->where(DB::raw('PGIT_RCVQT - COALESCE(SHP_QT, 0)'), '>', 0);

        if (!empty($po) || $po != '0') {
            $data->where('PPO1_PONO', 'LIKE', base64_decode($po) . "%");
        }

        if (count($data->get()) > 0) {
            if (!empty($col) || $col != 0) {
                $data = $data->select($col)
                    ->groupBy($col)
                    ->get()
                    ->pluck($col);
            } else {
                $data = $data->get();
            }

            return $this->handleResponse($data, 'Item found !');
        }

        return $this->handleError('No item found !');
    }

    public function getDataPagination(Request $req)
    {
        $data = YPOMaster::select(
            'YPO_MSTR_TBL.*',
            DB::raw("COALESCE((
                SELECT SUM(YSPDT_POQT) FROM YPO_STXI_PO_DET_TBL
                WHERE YMT_ID = YPO_MSTR_TBL.id
            ), 0) as REG_PO_QTY"),
            DB::raw("COALESCE((
                SELECT COUNT(*) FROM YPO_STXI_PO_DET_TBL
                WHERE YMT_ID = YPO_MSTR_TBL.id
            ), 0) as INV_QTY"),
            'mt.MITM_ITMD1',
            'mt.MITM_SPTNO',
            'mt2.MSUP_ABBRV',
            'mt2.MSUP_SUPNM'
        )
            ->join(DB::raw('MGSVR.VMI_EXIM.DBO.MITM_TBL as mt'), 'mt.MITM_ITMCD', 'YPO_ITMCD')
            ->join(DB::raw('MGSVR.VMI_EXIM.DBO.MSUP_TBL as mt2'), 'mt2.MSUP_SUPCD', 'mt.MITM_SUPCD')
            ->orderBy('YPO_TXID', 'desc');

        if ($req->has('filter')) {
            foreach ($req->filter as $key => $value) {
                $data->where($value['cols'], 'LIKE', $value['value'] . '%');
            }
        }

        if ($req->has('pagination')) {
            if (isset($req->pagination['sortBy'])) {
                $data->orderBy($req->pagination['sortBy'], $req->pagination['descending'] ? 'DESC' : 'ASC');
            }

            $data = $data->paginate($req->pagination['rowsPerPage'], [], 'page', $req->pagination['page']);
        } else {
            $data = $data->get();
        }

        updateInvoiceYPOManualQueue::dispatch()->onQueue('updateInvoiceYPOManualQueue');

        if (count($data) > 0) {
            return $this->handleResponse($data, 'Data found !');
        }

        return $this->handleError('No item found !');
    }

    public function exportExcel(Request $req)
    {
        $data = $this->getDataYpo();

        if ($req->has('filter')) {
            foreach ($req->filter as $key => $value) {
                $data->where($value['cols'], 'LIKE', $value['value'] . '%');
            }
        }

        $data = $data->get();

        // return $data;
        Excel::store(new ExportYPOManual($data), 'export_ypo_manual.xlsx', 'public');

        return 'storage/app/public/export_ypo_manual.xlsx';
    }

    public function getDataYpo($id = '', $idx = '')
    {
        $data = YPOMaster::select(
            'YPO_MSTR_TBL.*',
            'YSPDT_PONO',
            'YSPDT_INVNO',
            'YSPDT_POQT',
            DB::raw('CAST(PPO1_ISUDT AS DATE) PPO1_ISUDT'),
            'ORI_PGIT_RCVQT',
            DB::raw('CAST(PGIT_RCVDT AS DATE) PGIT_RCVDT'),
            DB::raw('CAST(PGRN_RCVDT AS DATE) PGRN_RCVDT'),
            'PGRN_RCVQT',
            'PIB_FINISH',
            'mt.MITM_ITMD1',
            'mt.MITM_SPTNO',
            'mt2.MSUP_ABBRV',
            'mt2.MSUP_SUPNM'
        )
            ->leftjoin('YPO_STXI_PO_DET_TBL', 'YPO_MSTR_TBL.id', 'YMT_ID')
            ->leftjoin('V_YPO_OS_GIT', function($f) {
                $f->on('PPO1_PONO', 'YSPDT_PONO');
                $f->on('YSPDT_INVNO', 'PGIT_SUPNO');
                $f->on('YPO_ITMCD', 'PGIT_ITMCD');
            })
            ->join(DB::raw('MGSVR.VMI_EXIM.DBO.MITM_TBL as mt'), 'mt.MITM_ITMCD', 'YPO_ITMCD')
            ->join(DB::raw('MGSVR.VMI_EXIM.DBO.MSUP_TBL as mt2'), 'mt2.MSUP_SUPCD', 'mt.MITM_SUPCD')
            ->orderBy('YPO_TXID', 'desc')
            ->orderBy('YPO_ITMCD');

        if (!empty($id)) {
            $data->where('YPO_TXID', $id);
        }

        if (!empty($idx)) {
            $data->where('YPO_MSTR_TBL.id', $idx);
        }

        return $data;
    }

    public function cekData()
    {
        $data = YPOSTXIPODet::join('YPO_MSTR_TBL', 'YMT_ID', 'YPO_MSTR_TBL.id')->whereNull('YSPDT_INVNO')->get();

        if (count($data) > 0) {

            $hasil = [];
            foreach ($data as $key => $value) {    
                $dataView = DB::connection('sqlsrv_ems2')->table('V_YPO_OS_GIT')
                    ->where('PPO1_PONO', $value->YSPDT_PONO)
                    ->where('PPO2_ITMCD', $value->YPO_ITMCD)
                    ->where('PGIT_RCVQT', '>=', $value->YSPDT_POQTY)
                    ->orderBy('PPO1_ISUDT', 'asc')
                    ->first();
                
                if (!empty($dataView)) {
                    $hasil[] = YPOSTXIPODet::where('YMT_ID', $value->YMT_ID)
                        ->where('YSPDT_PONO', $value->YSPDT_PONO)
                        ->update([
                            'YSPDT_INVNO' => $dataView->PGIT_SUPNO,
                            'YSPDT_POQT' => $dataView->PGIT_RCVQT
                        ]);
                }
            }

            if (count($hasil) > 0) {
                return 'update';
            }

            return 'tidak update';
        }        

        return 'tidak update';
    }

    public function uploadManualPO(Request $req)
    {
        ini_set('max_execution_time', '300');
        // $nama_file = $req->file->hashName();
        $file = new File($req->file);
        $extNya = $req->file('file')->getClientOriginalExtension();

        $fileHash = str_replace('.' . $file->extension(), '', $file->hashName());
        $nama_file = $fileHash . '.' . $extNya;

        // return $nama_file;
        $oriFileName = $req->file('file')->getClientOriginalName();

        if ($extNya == 'xls' || $extNya == 'xlsx') {
            $splitString = intval(preg_replace('/[^0-9]+/', '', $oriFileName), 10);


            $req->file->storeAs('/public/upload_manual_ymi_po/', $nama_file);

            if ($extNya == 'xls') {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
                $writer = new Xlsx($spreadsheet);
                $nama_file = $fileHash . '.xlsx';
                $writer->save('/public/upload_manual_ymi_po/' . $nama_file);
            }

            YPOMaster::truncate();
            YPOSTXIPODet::truncate();
            $importer = new ImportSTXIYEIDPOConfirmation();

            Excel::import($importer, public_path('/storage/upload_manual_ymi_po/' . $nama_file));

            return $this->handleResponse([], 'Upload Sukses ' . $nama_file);
        } else {
            return $this->handleError("File name doesn't right! please check again !");
        }
    }
}