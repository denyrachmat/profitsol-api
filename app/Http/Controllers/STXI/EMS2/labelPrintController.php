<?php

namespace App\Http\Controllers\STXI\EMS2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Http\Controllers\API\PORTAL\BaseController;
use Carbon\Carbon;
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
            return "Couldn't print to this printer: " . $e->getMessage() . "\n";
        }
    }

    public function searchItems(Request $request)
    {
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

    public function searchGIT(Request $request)
    {
        ini_set('memory_limit', '2048M');

        $hist = DB::connection('sqlsrv_mega_exim')->table('PGRN_TBL')
            ->select(
                DB::raw('PGRN_SUPNO as PGIT_SUPNO'),
                DB::raw('PGRN_ITMCD as PGIT_ITMCD'),
                'PGITSHP_SHPREFNO',
                DB::raw("CONCAT(RTRIM(MITM_ITMCD), '( ' , MITM_ITMD1, ' )') AS MITM_ITMD1"),
                'MITM_STKUOM',
                'MITM_SPTNO',
                'MITM_MAKERNM',
                'MITM_SPQ',
                'PGRN_SUPCD',
                'MSUP_SUPNM',
                DB::raw('MITM_SPQ AS SPQ_QTY'),
                DB::raw('sum(PGRN_RCVQT) AS TOTAL_QTY'),
                DB::raw('1 AS TOTAL_PRINT'),
                DB::raw('sum(PGRN_RCVQT) as PGIT_RCVQT'),
                DB::raw('CAST(PGRN_RCVDT AS DATE) PGRN_RCVDT')
            )
            ->join('MITM_TBL', 'MITM_ITMCD', 'PGRN_ITMCD')
            ->leftjoin('PGITSHP_TBL', 'PGITSHP_DOCNO', 'PGRN_SUPNO')
            ->join('MSUP_TBL', 'PGRN_SUPCD', 'MSUP_SUPCD')
            // ->join('PGIT_TBL', 'PGIT_SUPNO', 'PGRN_SUPNO')
            ->groupBy(
                'PGRN_SUPNO',
                'PGRN_ITMCD',
                'PGITSHP_SHPREFNO',
                DB::raw("CONCAT(RTRIM(MITM_ITMCD), '( ' , MITM_ITMD1, ' )')"),
                'MITM_STKUOM',
                'MITM_SPTNO',
                'MITM_SPQ',
                'PGRN_LUPDT',
                'MSUP_SUPNM',
                'PGRN_SUPCD',
                'PGRN_RCVDT',
                'MITM_MAKERNM'
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
        } else {
            $hist->whereBetween('PGRN_RCVDT', [Carbon::now()->subDays(1), date('Y-m-d')]);
        }

        if ((clone $hist)->count() > 0) {
            $datanya = (clone $hist)->orderBy('PGRN_LUPDT', 'desc')
                // ->limit(10)
                ->get()
                ->toArray();

            $hasil = [];
            foreach (@json_decode(json_encode($datanya), true) as $key => $value) {
                $hasil[$value['PGITSHP_SHPREFNO']]['PGITSHP_SHPREFNO'] = $value['PGITSHP_SHPREFNO'];
                $hasil[$value['PGITSHP_SHPREFNO']]['det'][] = array_merge(
                    $value,
                    // [
                    //     'SPLIT_SPQ' => $this->splitStockBySPQ(
                    //         (int)$value['MITM_SPQ'],
                    //         (int)$value['PGIT_RCVQT'],
                    //         $value
                    //     )
                    // ]
                );
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

    public function splitData(Request $request)
    {
        $dataDetail = $request->data['det'] ?? [];
        usort($dataDetail, function ($a, $b) {
            return $a['SPQ_QTY'] <=> $b['SPQ_QTY'];
        });

        $splitSPQList = $this->splitStockBySPQ(
            (int) $request->data['SPQ_QTY'],
            (int) $request->data['TOTAL_QTY'],
            $request->data
        );

        $hasilSplit = [];
        $jumPrint = 0;
        foreach ($splitSPQList as $key => $value) {
            if ($key === 0 || $value['SPQ_QTY'] == $splitSPQList[$key - 1]['SPQ_QTY']) {
                $jumPrint++;
            } else {
                $jumPrint = 1;
            }

            $hasilSplit[$value['SPQ_QTY']] = array_merge(
                $value,
                [
                    'TOTAL_PRINT' => $jumPrint,
                    'SPQ_QTY' => $value['SPQ_QTY'],
                    'TOTAL_QTY' => $value['TOTAL_QTY']
                ]
            );
        }
        return array_merge($request->data, ['det' => array_values($hasilSplit)]);
    }

    public function splitStockBySPQ($spq, $qty, $data, $currentDet = [], $returnedData = []): array
    {
        if (isset($data['det']) && empty($currentDet)) {
            $currentDet = $data['det'];
        }

        $nowDetData = current($currentDet);
        $spq = $nowDetData ? $nowDetData['SPQ_QTY'] : $spq;
        if ($qty > $spq) {
            $cekNextData = next($currentDet);
            if (!empty($cekNextData)) {
                if (($spq + (int)$cekNextData['SPQ_QTY']) > $qty) {
                    return $this->splitStockBySPQ($cekNextData['SPQ_QTY'], $qty, $data, $currentDet, $returnedData);
                } else {
                    prev($currentDet);
                }
            }

            $data['SPQ_QTY'] = $spq;
            $returnedData[] = $data;
            return $this->splitStockBySPQ($spq, $qty - $spq, $data, $currentDet, $returnedData);
        } else {
            $cekNextData = next($currentDet);
            if (!empty($cekNextData)) {
                $spq = $cekNextData['SPQ_QTY'];
                return $this->splitStockBySPQ($spq, $qty, $data, $currentDet, $returnedData);
            }

            $data['SPQ_QTY'] = $qty;
            $returnedData[] = $data;
            return $returnedData;
        }
    }
}
