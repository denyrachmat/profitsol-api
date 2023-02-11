<?php

namespace App\Http\Controllers\STXI\EMS2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
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

    public function getReport(Request $req)
    {
        $begin = new \DateTime($req->fdate);
        $end = new \DateTime($req->ldate);

        $interval = \DateInterval::createFromDateString('1 month');
        $period = new \DatePeriod($begin, $interval, $end);

        $hasil = [];
        foreach ($period as $dt) {
            $data = DB::connection('sqlsrv_ems2')
                ->table('V_FRCST_DLV_SHP')
                ->select(
                    'MITM_ITMCD',
                    'MITM_ITMD1',
                    'MITM_SPTNO',
                    DB::raw('SUM(SSHP_SHPQT) AS SSHP_SHPQT'),
                    DB::raw('SUM(FDT_QTY) AS FDT_QTY')
                )
                ->whereBetween('SSHP_SHPDT', [$dt->format("Y-m-1"), date('Y-m-t', strtotime($dt->format("Y-m-1")))])
                ->groupBy(
                    'MITM_ITMCD',
                    'MITM_ITMD1',
                    'MITM_SPTNO'
                )
                ->get();

            $hasil[$dt->format('Y-m')] = [
                'full_date' => $dt->format("Y M"),
                'data' => $data 
            ];
        }

        return $hasil;
    }
}
