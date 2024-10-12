<?php

namespace App\Http\Controllers\STXI\LOG;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\STXI\LOG\INSWDataMaster;
use App\Models\STXI\LOG\INSWDataDocBeaMaster;
use App\Models\STXI\LOG\INSWDataRegDet;

class HSCodeReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function HSCodeFilter(Request $request): array
    {
        $data = INSWDataMaster::select('*');

        if (
            count($request->filter) > 0 && count(array_filter($request->filter, function ($f) {
                return !empty($f['value']);
            })) > 0
        ) {
            foreach ($request->filter as $key => $value) {
                $data->where($value['cols'], $value['param'], $value['param'] === 'like' ? "%{$value['value']}%" : $value['value']);
            }
        }

        return $data->get()->toArray();
    }

    public function HSCodeBeaDetail() {
        return INSWDataDocBeaMaster::get();
    }

    public function HSCodeRegulationDet($hsCode) {
        return INSWDataRegDet::where('ZID_HSCODE', $hsCode)->get();
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
        //
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
