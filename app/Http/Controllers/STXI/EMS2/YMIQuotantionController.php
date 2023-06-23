<?php

namespace App\Http\Controllers\STXI\EMS2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\API\PORTAL\BaseController;

use App\Models\STXI\EMS2\YMI_QUO_TBL;
use Illuminate\Support\Facades\DB;
class YMIQuotantionController extends BaseController
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

    public function getData(Request $req)
    {
        $data = YMI_QUO_TBL::from( 'YMI_QUO_MSTR_TBL as A' )
        ->select(
            'A.YQMT_ITMCD',
            'A.YQMT_QUO_NO',
            'A.YQMT_BP',
            'A.YQMT_SP',
            DB::raw('A.YQMT_SP - A.YQMT_BP as DIFF'),
            DB::raw('CAST((CASE WHEN A.YQMT_BP = 0 THEN 0 ELSE ((A.YQMT_SP - A.YQMT_BP) / A.YQMT_BP) * 100 END) AS DECIMAL(15,2)) as MU'),
            DB::raw('CAST((CASE WHEN A.YQMT_SP = 0 THEN 0 ELSE ((A.YQMT_SP - A.YQMT_BP) / A.YQMT_SP) * 100 END) AS DECIMAL(15,2)) as GP'),
            'A.YQMT_RATE',
            'A.YQMT_SP_RPH',
            'A.YQMT_BGNDT',
            'A.YQMT_ENDDT',
            'A.YMQT_REMARK',
            'A.YMQT_REMARK2',
        )->join(
            'MGSVR.VMI_EXIM.dbo.MITM_TBL', 'MITM_ITMCD', 'A.YQMT_ITMCD'
        )->join(DB::raw("(
            SELECT YQMT_ITMCD, MAX(ID) AS maxid FROM YMI_QUO_MSTR_TBL
            group by YQMT_ITMCD
        ) aa "), function($j) {
            $j->on('A.YQMT_ITMCD', 'aa.YQMT_ITMCD');
            $j->on('A.id', 'aa.maxid');
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

    public function listDetail($item){
        
    }
}
