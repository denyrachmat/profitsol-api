<?php

namespace App\Http\Controllers\STXI\EMS2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\API\PORTAL\BaseController;
use DB;
use Excel;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\File;

use App\Models\STXI\EMS2\YMI_QUO_TBL;
use App\Models\STXI\EMS2\YMI_PO_PRC_MSTR_TBL;
use App\Imports\STXI\EMS2\ImportYMIPriceList;
use App\Exports\STXI\EMS2\ExportYMIPriceList;

class YMICDCUController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = DB::table('MGSVR.VMI_EXIM.dbo.Z_STXI_V_YEID_PO_SUPP_LIST')->paginate();

        return $this->handleResponse($data, 'Data found !');
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        //
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

    public function getData(Request $req)
    {
        $data = DB::connection('sqlsrv_ems2')->table('MGSVR.VMI_EXIM.dbo.Z_STXI_V_YEID_PO_SUPP_LIST as zsvypsl')
            ->select(
                'zsvypsl.*', 
                DB::raw('CASE WHEN yqmt.YPPMT_BP IS NULL THEN NULL ELSE zsvypsl.PO_LINE END as YPPMT_POLNO'), 
                'yqmt.*'
            )
            ->leftJoin(DB::raw("(
                SELECT
                    YQMT_ITMCD as YPPMT_ITMCD,
                    YQMT_BP as YPPMT_BP,
                    YQMT_SP AS YQMT_SP
                FROM YMI_QUO_MSTR_TBL
                GROUP BY 
                    YQMT_ITMCD,
                    YQMT_BP,
                    YQMT_SP
            ) yqmt"), function($j) {
                $j->on('ITEM_CODE', 'yqmt.YPPMT_ITMCD');
                $j->on('SUPP_PRICE', DB::raw('CAST(yqmt.YPPMT_BP AS DECIMAL(15,5))'));
            });

        if ($req->has('filter')) {
            foreach ($req->filter as $key => $value) {
                if (strpos(strtolower($value['cols']), 'date') !== false) {
                    $data->where($value['cols'], $value['value']);
                } else {
                    $data->where($value['cols'], 'LIKE', $value['value'] . '%');
                }
            }
        }


        // return $data->toSql();
        if ($req->has('pagination')) {
            if (isset($req->pagination['sortBy'])) {
                $data->orderBy($req->pagination['sortBy'], $req->pagination['descending'] ? 'DESC' : 'ASC');
            }

            $data = $data->paginate($req->pagination['rowsPerPage'], [], 'page', $req->pagination['page']);
        } else {
            $data = $data->get();
        }

        return $this->handleResponse($data, 'Data found !');
    }

    public function uploadPriceList(Request $req)
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


            $req->file->storeAs('/public/upload_price_ymi/', $nama_file);

            if ($extNya == 'xls') {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
                $writer = new Xlsx($spreadsheet);
                $nama_file = $fileHash . '.xlsx';
                $writer->save('/public/upload_price_ymi/' . $nama_file);
            }

            YMI_QUO_TBL::truncate();
            $importer = new ImportYMIPriceList();

            Excel::import($importer, public_path('/storage/upload_price_ymi/' . $nama_file));

            return $this->handleResponse([], 'Upload Sukses ' . $nama_file);
        } else {
            return $this->handleError("File name doesn't right! please check again !");
        }
    }

    public function registerPO(Request $req)
    {
        $data = DB::connection('sqlsrv_ems2')->table('MGSVR.VMI_EXIM.dbo.Z_STXI_V_YEID_PO_SUPP_LIST')
            ->leftJoin(DB::raw('
                (
                    SELECT DISTINCT
                        YQMT_ITMCD
                    FROM YMI_QUO_MSTR_TBL
                ) A
            '), function ($j) {
                $j->on('ITEM_CODE', 'A.YQMT_ITMCD');
            })
            ->where('PO_NO', $req->po)
            ->get();

        $hasil = [];
        $dataHasil = [];
        foreach ($data as $key => $value) {
            if (empty($value->YQMT_ITMCD)) {
                $hasil[] = [
                    'status' => false,
                    'data' => $value,
                    'message' => 'Item: ' . $value->ITEM_CODE . ', is not registered on Price List.'
                ];
            } else {
                $dataHasil[] = $value;
                $hasil[] = [
                    'status' => true,
                    'data' => $value,
                    'message' => 'Item: ' . $value->ITEM_CODE . ', registered on price'
                ];
            }
        }

        if (count(array_filter($hasil, function ($f) {
            return !$f['status']; })) > 0) {
            return $this->handleError('Some item not registered !', $hasil);
        } else {

            foreach ($dataHasil as $key2 => $value2) {
                $checkPrice = YMI_QUO_TBL::where('YQMT_ITMCD', $value2->ITEM_CODE)->orderBy('id', 'desc')->first();
                YMI_PO_PRC_MSTR_TBL::create([
                    'YPPMT_PONO' => $value2->PO_NO,
                    'YPPMT_POLNO' => $value2->PO_LINE,
                    'YPPMT_ITMCD' => $value2->ITEM_CODE,
                    'YQMT_SP' => $checkPrice->YQMT_SP,
                    'YQMT_ID' => $checkPrice->id,
                ]);
            }

            return $this->handleResponse($hasil, 'Data PO Registered !');
        }
    }

    public function exportExcel(Request $req) {
        ini_set('memory_limit', '2G');
        $data = DB::connection('sqlsrv_ems2')->table('MGSVR.VMI_EXIM.dbo.Z_STXI_V_YEID_PO_SUPP_LIST')->select(
            'SUPP_CD',
            'SUPP_CURR',
            'SUPP_NM',
            'PO_NO',
            'PO_LINE',
            'ITEM_CODE',
            'MK_PART_NO',
            'ITEM_DESC',
            'PO_DATE',
            'ETA_DATE',
            'SUPP_PRICE',
            'PO_QTY',
            'GIT_DATE',
            'GIT_QTY',
            'GIT_DOCNO',
            'PURC_AMT',
            'YQMT_SP',
            DB::raw('PO_QTY * YQMT_SP as SALES_AMNT'),
            DB::raw('(PURC_AMT - (PO_QTY * YQMT_SP)) as ENJ_AMNT'),
            DB::raw("CASE WHEN PURC_AMT - (PO_QTY * YQMT_SP) < 0
                THEN 'CU Price'
                ELSE 
                    CASE WHEN PURC_AMT - (PO_QTY * YQMT_SP) > 0
                        THEN 'CD Price'
                        ELSE '-'
                    END
            END as REMARKS"),
        )
        ->join(DB::raw("(
            SELECT
                YQMT_ITMCD as YPPMT_ITMCD,
                YQMT_BP as YPPMT_BP,
                YQMT_SP AS YQMT_SP
            FROM YMI_QUO_MSTR_TBL
            GROUP BY 
                YQMT_ITMCD,
                YQMT_BP,
                YQMT_SP
        ) yqmt"), function($j) {
            $j->on('ITEM_CODE', 'yqmt.YPPMT_ITMCD');
            $j->on('SUPP_PRICE', DB::raw('CAST(yqmt.YPPMT_BP AS DECIMAL(15,5))'));
        });

        if ($req->has('filter')) {
            foreach ($req->filter as $key => $value) {
                if (strpos(strtolower($value['cols']), 'date') !== false) {
                    $data->where($value['cols'], $value['value']);
                } else {
                    $data->where($value['cols'], 'LIKE', $value['value'] . '%');
                }
            }
        }

        // return $data;
        Excel::store(new ExportYMIPriceList($data->get()), 'export_price_cdcu.xlsx', 'public');

        return 'storage/app/public/export_price_cdcu.xlsx';
    }

}