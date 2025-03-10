<?php

namespace App\Http\Controllers\STXI\IT;

use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Http\Request;
use App\Models\STXI\IT\PartScanner;

class PartScannerController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        foreach ($request->data as $key => $value) {
            PartScanner::updateOrCreate([
                'MBCSCNH_ITMCD' => $value['MBCSCNH_ITMCD'],
                'MBCSCNH_QTY' => $value['MBCSCNH_QTY'],
                'MBCSCNH_LOT' => $value['MBCSCNH_LOT'],
            ],[
                'MBCSCNH_ITMCD' => $value['MBCSCNH_ITMCD'],
                'MBCSCNH_QTY' => $value['MBCSCNH_QTY'],
                'MBCSCNH_LOT' => $value['MBCSCNH_LOT'],
                'MBCSCNH_VALID' => $value['MBCSCNH_VALID'],
                'MBCSCNH_REMARKS' => $value['MBCSCNH_REMARKS'],
                'created_by' => $value['created_by']
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {

        $store = PartScanner::where('created_by', $id)
            ->join('CRPTWEB.dbo.VIEW_MITM_TBL', 'MITM_ITMCD', 'MBCSCNH_ITMCD')
            ->get();

        return $this->handleResponse($store, 'Data found !');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
