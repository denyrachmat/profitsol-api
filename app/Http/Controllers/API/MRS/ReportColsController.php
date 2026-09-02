<?php

namespace App\Http\Controllers\API\MRS;

use App\Http\Controllers\API\PORTAL\BaseController as BaseController;
use App\Models\MRS\MRSReportMstr;
use Illuminate\Http\Request;
use App\Models\MRS\MRSReportColsDet;
use App\Traits\MRS\ConnectionDBTraits;
use Illuminate\Support\Facades\DB;

class ReportColsController extends BaseController
{
    use ConnectionDBTraits;
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
        $master = MRSReportMstr::where('id', $id)->first();

        $data = MRSReportColsDet::select(
            'mrs_report_cols_det.*',
            DB::raw('mrs_report_mstr.mrm_name as tbl_nm')
        )->join('mrs_report_mstr', 'mrm_id', 'mrs_report_mstr.id')
        ->where('mrcd_col_prop', 'cols')
        ->where('mrm_id', $id)
        ->where('mrcd_isActive', 1)
        ->get();

        $dataParam = MRSReportColsDet::select(
            'mrs_report_cols_det.*',
            DB::raw('mrs_report_mstr.mrm_name as tbl_nm')
        )->join('mrs_report_mstr', 'mrm_id', 'mrs_report_mstr.id')
        ->where('mrcd_col_prop', 'params')
        ->where('mrm_id', $id)
        ->where('mrcd_isActive', 1)
        ->get();

        return $this->handleResponse([
            'title' => $data[0]->tbl_nm,
            'cols' => $this->getCols($data),
            'colsParam' => $this->getCols($dataParam),
            'props' => $master->mrm_url_gen,
            'filterFirst' => $master->mrm_filter_flg,
            'idForms' => (stripos($master->mrm_url_gen, 'cms') !== false || stripos($master->mrm_url_gen, 'rpa') !== false)
                ? $master->mrm_query
                : null,
        ], 'Data Found');
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
}
