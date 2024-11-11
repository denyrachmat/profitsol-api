<?php

namespace App\Http\Controllers\STXI\EMS2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Http\Controllers\API\PORTAL\BaseController;

use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

class labelPrintController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $connector = new WindowsPrintConnector('STXI326-PC/SATO CL4NX 305dpi');
            $printer = new Printer($connector);
            $printer->text("Hello World!\n");
            $printer->cut();
            $printer->close();
        } catch (\Exception $e) {
            return "Couldn't print to this printer: " . $e -> getMessage() . "\n";
        }
    }

    public function searchItems(Request $request) {
        $hist = DB::connection('sqlsrv_mega_exim')->table('MITM_TBL')
        ->select(
            'MITM_ITMCD',
            DB::raw("CONCAT(RTRIM(MITM_ITMCD), '( ' , MITM_ITMD1, ' )') AS MITM_ITMD1"),
            'MITM_STKUOM',
            'MITM_SPTNO'
        );

        if ($request->has('filter') && count($request->filter) > 0) {
            foreach ($request->filter as $key => $value) {
                if (isset($value['step']) && $value['step'] === 'or') {
                    $hist = (clone $hist)->orwhere($value['cols'], $value['param'], $value['param'] === 'like' ? "%{$value['value']}%" : $value['value']);
                } else {
                    $hist = (clone $hist)->where($value['cols'], $value['param'], $value['param'] === 'like' ? "%{$value['value']}%" : $value['value']);
                }
            }
        }

        if ((clone $hist)->count() > 0) {
            $datanya = (clone $hist)
                ->get();
            return $this->handleResponse($datanya, 'Data Fetched');
        } else {
            return $this->handleError('No data found !!', []);
        }
    }

    public function searchGIT(Request $request) {
        $hist = DB::connection('sqlsrv_mega_exim')->table('PGIT_TBL')
        ->select(
            'PGIT_SUPNO',
            'PGIT_ITMCD',
            'PGITSHP_SHPREFNO',
            DB::raw("CONCAT(RTRIM(MITM_ITMCD), '( ' , MITM_ITMD1, ' )') AS MITM_ITMD1"),
            'MITM_STKUOM',
            'MITM_SPTNO',
            DB::raw('sum(PGIT_RCVQT) as PGIT_RCVQT')
        )
        ->join('MITM_TBL', 'MITM_ITMCD', 'PGIT_ITMCD')
        ->join('PGITSHP_TBL', 'PGITSHP_DOCNO', 'PGIT_SUPNO')
        ->groupBy(
            'PGIT_SUPNO',
            'PGIT_ITMCD',
            'PGITSHP_SHPREFNO',
            DB::raw("CONCAT(RTRIM(MITM_ITMCD), '( ' , MITM_ITMD1, ' )')"),
            'MITM_STKUOM',
            'MITM_SPTNO',
            'PGIT_LUPDT'
        );

        if ($request->has('filter') && count($request->filter) > 0) {
            foreach ($request->filter as $key => $value) {
                if (!empty($value['value'])) {
                    if (isset($value['step']) && $value['step'] === 'or') {
                        $hist->orwhere($value['cols'], $value['param'], $value['param'] === 'like' ? "%{$value['value']}%" : $value['value']);
                    } else {
                        $hist->where($value['cols'], $value['param'], $value['param'] === 'like' ? "%{$value['value']}%" : $value['value']);
                    }
                }
            }
        }

        if ((clone $hist)->count() > 0) {
            $datanya = (clone $hist)->orderBy('PGIT_LUPDT', 'desc')
                ->limit(50)
                ->get()
                ->toArray();
            $hasil = [];
            foreach (@json_decode(json_encode($datanya), true) as $key => $value) {
                $hasil[$value['PGITSHP_SHPREFNO']]['PGITSHP_SHPREFNO'] = $value['PGITSHP_SHPREFNO'];
                $hasil[$value['PGITSHP_SHPREFNO']]['det'][] = $value;
            }

            return $this->handleResponse(array_values($hasil), 'Data Fetched');
        } else {
            return $this->handleError('No data found !!', []);
        }
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
