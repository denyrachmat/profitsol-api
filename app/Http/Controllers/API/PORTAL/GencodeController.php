<?php

namespace App\Http\Controllers\API\PORTAL;

use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Http\Request;
use App\Models\PORTAL\PortalGencode;

class GencodeController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => PortalGencode::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = PortalGencode::updateOrCreate([
            'id' => $request->id,
        ],[
            'pgm_code' => $request->pgm_code,
            'pgm_desc' => $request->pgm_desc,
            'pgm_desc2' => $request->pgm_desc2,
            'pgm_desc3' => $request->pgm_desc3,
            'pgm_value' => $request->pgm_value,
            'pgm_value2' => $request->pgm_value2,
            'pgm_value3' => $request->pgm_value3,
            'pgm_created_by' => $request->header('username'),
        ]);

        return $this->handleResponse(
            $data,
            'Store Successfull !'
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return $this->handleResponse(
            PortalGencode::where('pgm_code', $id)->get(),
            'Data Found !'
        );
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
