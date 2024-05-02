<?php

namespace App\Http\Controllers\STXI\LOG;

use App\Http\Controllers\API\PORTAL\BaseController;
use App\Models\STXI\CEISA40\viewCeisaRespon;
use Illuminate\Http\Request;
use App\Models\STXI\CEISA40\CEISARESPON;
use Illuminate\Support\Facades\DB;
use App\Models\STXI\LOG\BCMega;

class CeisaMonitoringController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = viewCeisaRespon::orderBy('TGL_DAFTAR', 'DESC')->get()->toArray();

        $hasil = [];
        foreach ($data as $key => $value) {
            // $cekStatBCMega = DB::connection('sqlsrv_itinv')->table('VEW_BCDOC')->where('CBCDOC_BCDOCNO', $value['NOMOR_DAFTAR'])->where('CBCDOC_BCDOCDT', $value['TGL_DAFTAR'])->first();
            $hasil[] = array_merge(
                $value,
            );
        }

        return $this->handleResponse($hasil, 'Data fetched ');
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
    public function show($id, $daftar)
    {
        $data = CEISARESPON::where('NOMOR_AJU', $id)
            ->where('NOMOR_DAFTAR', $daftar)
            ->orderBy('TGL_DAFTAR', 'DESC')
            ->get();

        return $this->handleResponse($data, 'Data fetched ');
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

    public function resyncITInventory(){
        
    }

    public function interfaceBCDOCMEGAtoWEB(){
        set_time_limit(3600);

        $cekStatBCMega = DB::connection('sqlsrv_itinv')->table('VEW_BCDOC')->get();

        foreach ($cekStatBCMega as $key => $value) {
            BCMega::updateOrCreate([
                'BCMG_BCDOCNO' => $value->CBCDOC_BCDOCNO,
                'BCMG_BCDOCDT' => $value->CBCDOC_BCDOCDT,
            ],[
                'BCMG_TYPE' => $value->CBCDOC_BCTYPE,
                'BCMG_BCDOCNO' => $value->CBCDOC_BCDOCNO,
                'BCMG_BCDOCDT' => $value->CBCDOC_BCDOCDT,
            ]);
        }

        return 'sukses';
    }

    public function mergeDownloadCeisa40(){
        
    }
}
