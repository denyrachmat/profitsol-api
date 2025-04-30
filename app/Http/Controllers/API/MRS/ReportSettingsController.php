<?php

namespace App\Http\Controllers\API\MRS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MRS\MRSReportMstr;
use App\Models\MRS\MRSReportActionDet;

class ReportSettingsController extends Controller
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
        if($request->has('header')){
            $header = $request->header;
            MRSReportMstr::where('id', $request->id)->update([
                'mrm_filter_flg' => $header['mrm_filter_flg'],
                'mrm_dist_exp_flag' => $header['mrm_dist_exp_flag']
            ]);

            return response()->json($header);
        } else {
            return response()->json(['error' => 'Header not found'], 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
