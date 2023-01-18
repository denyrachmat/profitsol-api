<?php

namespace App\Http\Controllers\STXI\PU;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\STXI\PU\PAApproval;
use App\Models\STXI\PU\PAApprovalMS;
use App\Http\Controllers\API\PORTAL\BaseController;
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
        if (empty(PAApprovalMS::where('PAINSNO', $id)->first())) {
            return $this->handleError('Update data gagal, ID tidak di temukan !');
        }

        if ($table = 'PAINS_PRTCHG_MS') {
            $hasil = PAApprovalMS::where('PAINSNO', $id)->update([
                'ACKG_DIR_DT' => date('Y-m-d H:i:s'),
                'ACKG_DIR' => $username
            ]);
        } else {
            $hasil = PAApproval::where('PAINSNO', $id)->update([
                'ACKG_DIR_DT' => date('Y-m-d H:i:s'),
                'ACKG_DIR' => $username
            ]);
        }

        if ($hasil) {
            return $this->handleResponse(PAApprovalMS::where('PAINSNO', $id)->first(), 'Update Sukses !');
        } else {
            return $this->handleError('Update data gagal !');
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
