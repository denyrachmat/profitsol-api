<?php

namespace App\Http\Controllers\STXI\EMS2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\STXI\EMS2\FRCST_DLV_TYO;
use Excel;

use App\Exports\STXI\ExportForcastDLVTYO;

class ForcastDOTYOController extends Controller
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
        FRCST_DLV_TYO::where('FDT_MONTH', date('m', strtotime(($request->fdate))))->where('FDT_YEAR', date('Y', strtotime(($request->ldate))))->delete();
        $hasil = [];
        foreach ($request->data as $key => $value) {
            $cekSameItem = array_filter($hasil, function ($f) use($value){
                return $f['FDT_ITMCD'] === $value[0];
            });
            
            $hasil[] = [
                'FDT_ITMCD' => $value[0],
                'FDT_MONTH' => date('m', strtotime(($request->fdate))),
                'FDT_YEAR' => date('Y', strtotime(($request->ldate))),
                'FDT_QTY' => (int)$value[1],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }

        // return $hasil;

        $insert = FRCST_DLV_TYO::insert($hasil);

        return $insert;
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

    public function getReport(Request $req)
    {
        $begin = new \DateTime($req->fdate);
        $end = new \DateTime($req->ldate);

        $interval = \DateInterval::createFromDateString('1 month');
        $period = new \DatePeriod($begin, $interval, $end);

        $hasil = [];
        foreach ($period as $dt) {
            $data = DB::connection('sqlsrv_ems2')
                ->table('V_FRCST_DLV_SHP as vfds')
                ->select(
                    'vfds.MITM_ITMCD',
                    'vfds.MITM_ITMD1',
                    'vfds.MITM_SPTNO',
                    DB::raw('SUM(vfds.SSHP_SHPQT) AS SSHP_SHPQT'),
                    DB::raw('(
                        SELECT COALESCE(SUM(FDT_QTY),0) FROM FRCST_DLV_TYO as fdt
                        WHERE fdt.FDT_ITMCD = vfds.MITM_ITMCD
                        AND fdt.FDT_MONTH = MONTH(MAX(vfds.SSHP_SHPDT))
                        AND fdt.FDT_YEAR = YEAR(MAX(vfds.SSHP_SHPDT))
                    ) AS FDT_QTY')
                )
                ->whereBetween('SSHP_SHPDT', [$dt->format("Y-m-1"), date('Y-m-t', strtotime($dt->format("Y-m-1")))])          
                ->groupBy(
                    'MITM_ITMCD',
                    'MITM_ITMD1',
                    'MITM_SPTNO'
                )
                ->get();

            // if ($dt->format("Y-m-1") == '2022-02-1') {
            //     return $data;
            // }
            
            $hasil[$dt->format('Y-m')] = [
                'full_date' => $dt->format("Y M"),
                'range_date' => [$dt->format("Y-m-01"), date('Y-m-t', strtotime($dt->format("Y-m-1")))],
                'data' => (array)$data->toArray() 
            ];
        }

        return $hasil;
    }

    public function getItemList($search = '')
    {
        $data = DB::connection('sqlsrv_ems2')
            ->table('MGSVR.VMI_EXIM.dbo.MITM_TBL');

        if (!empty($search)) {
            $data->where('MITM_ITMCD', 'like', base64_decode($search).'%');
        }

        $hasil = []; 
        foreach ($data->get()->pluck('MITM_ITMCD') as $key => $value) {
            $hasil[] = trim($value);
        }

        return $hasil;
    }

    public function exportForcast(Request $req)
    {
        $data = $this->getReport(new Request($req->all()));

        Excel::store(new ExportForcastDLVTYO($data), 'export_do_tyo_forcast.xlsx', 'public');

        return 'storage/app/public/export_do_tyo_forcast.xlsx';
    }

    public function dataExport($data)
    {
        $hasil = [];
        $no = 1;
        foreach (array_values($data) as $key => $value) {
            // Item Only
            if ($key === 0) {
                foreach ($value['data'] as $keyDet => $valueDet) {
                    $hasil[] = [
                        0 => $no,
                        1 => trim($valueDet->MITM_ITMCD),
                        // 'FDT_QTY_'.$key => $valueDet->FDT_QTY,
                        // 'SSHP_SHPQT_'.$key => $valueDet->SSHP_SHPQT,
                    ];

                    $no++;
                }
            } else {
                foreach ($value['data'] as $keyDet => $valueDet) {
                    $checkExistsItem = array_filter(array_values($hasil), function ($f) use ($valueDet) {
                        return $f[1] === trim($valueDet->MITM_ITMCD);
                    }, ARRAY_FILTER_USE_BOTH);
                    if (count($checkExistsItem) === 0) {
                        $hasil[] = [
                            0 => $no,
                            1 => trim($valueDet->MITM_ITMCD),
                            // 'FDT_QTY_'.$key => $valueDet->FDT_QTY,
                            // 'SSHP_SHPQT_'.$key => $valueDet->SSHP_SHPQT,
                        ];
    
                        $no++;
                    }
                }
            }
        }

        // return $hasil;

        foreach ($hasil as $keyCont => $valueCont) {
            foreach (array_values($data) as $key2 => $value2) {
                $findItem = array_values(array_filter(json_decode(json_encode($value2['data']), true), function ($f) use ($valueCont) {
                    return trim($f['MITM_ITMCD']) === $valueCont[1];
                }, ARRAY_FILTER_USE_BOTH));

                if (isset($findItem[0]) && count($findItem) > 0) {
                    array_push($hasil[$keyCont], (int)$findItem[0]['FDT_QTY'], $findItem[0]['SSHP_SHPQT']);
                } else {
                    array_push($hasil[$keyCont], 0, 0);
                }
            }
        }

        return $hasil;
    }
}
