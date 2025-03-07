<?php

namespace App\Http\Controllers\API\PORTAL;

use Illuminate\Http\Request;
use App\Models\PORTAL\PortalMobileGencode;
use App\Http\Controllers\API\PORTAL\BaseController;

class MobileGencodeController extends BaseController
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
        $store = PortalMobileGencode::updateOrCreate([
            'MBLG_SETTYPE' => $request->MBLG_SETTYPE,
            'MBLG_SETVALUE' => $request->MBLG_SETVALUE,
        ],[
            'MBLG_SETTYPE' => $request->MBLG_SETTYPE,
            'MBLG_SETVALUE' => $request->MBLG_SETVALUE,
            'MBLG_SETDESC' => $request->MBLG_SETDESC,
            'created_by' => $request->created_by,
        ]);

        return $this->handleResponse($store, 'Data has been updated !');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $store = PortalMobileGencode::where('created_by', $id)->get();

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
