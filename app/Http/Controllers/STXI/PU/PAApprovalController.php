<?php

namespace App\Http\Controllers\STXI\PU;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\STXI\PU\PAApproval;
use App\Models\STXI\PU\PAApprovalMS;
use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Support\Facades\DB;
class PAApprovalController extends BaseController
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
    public function show($id, $username = '', $table = '')
    {
        if (empty(PAApprovalMS::where('PAINSNO', $id)->first()) && empty(PAApproval::where('PAINSNO', $id)->first())) {
            return $this->handleError('Update data gagal, ID tidak di temukan !');
        }

        if ($table == 'PAINS_PRTCHG_MS') {
            $data = DB::connection('sqlsrv_pu')->table('PAINS_PRTCHG_MS')->where('PAINSNO', $id)->first();
            if (empty($data->ACKG_DIR_DT)) {
                $hasil = DB::connection('sqlsrv_pu')->table('PAINS_PRTCHG_MS')->where('PAINSNO', $id)->update([
                    'ACKG_DIR_DT' => date('Y-m-d H:i:s'),
                    'ACKG_DIR' => $username
                ]); 

                if ($hasil) {
                    return $this->handleResponse(PAApprovalMS::where('PAINSNO', $id)->first(), 'Update Sukses !');
                } else {
                    return $this->handleError('Update data gagal !', $hasil);
                }
            } else {
                return $this->handleError('PA No Already approved !', []);
            }
        } else {
            $data = DB::connection('sqlsrv_pu')->table('PAINS_PRTCHG')->where('PAINSNO', $id)->first();
            if (empty($data->ACKG_DIR_DT)) {
                $hasil = DB::connection('sqlsrv_pu')->table('PAINS_PRTCHG')->where('PAINSNO', $id)->update([
                    'ACKG_DIR_DT' => date('Y-m-d H:i:s'),
                    'ACKG_DIR' => $username
                ]); 

                if ($hasil) {
                    return $this->handleResponse(PAApproval::where('PAINSNO', $id)->first(), 'Update Sukses !');
                } else {
                    return $this->handleError('Update data gagal !', $hasil);
                }
            } else {
                return $this->handleError('PA No Already approved !', []);
            }
        }
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
