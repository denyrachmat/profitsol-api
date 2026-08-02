<?php

namespace App\Http\Controllers\STXI\IT;

use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Http\Request;
use App\Models\STXI\IT\PartScanner;
use Illuminate\Support\Facades\DB;

use App\Traits\PORTAL\GencodeTraits;

class PartScannerController extends BaseController
{
    use GencodeTraits;
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
            ], [
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

    // Custom function to get DO From Mega

    public function getWHFromMega(Request $request)
    {
        $data = DB::connection('sqlsrv_mega_wms_exim')
            ->table('MWWHS_TBL')
            ->select('MWWHS_WHSCD', DB::raw("CONCAT(RTRIM(MWWHS_WHSCD), ' (', RTRIM(MWWHS_WHSNM), ')' ) AS MWWHS_WHSNM"))
            ->distinct()
            ->when($request->has('filters'), function ($query) use ($request) {
                foreach ($request->filters as $key => $valueFilter) {
                    $query->where($valueFilter['cols'], $valueFilter['param'], $valueFilter['value']);
                }
            })
            ->get();

        if ($data->isEmpty()) {
            return $this->handleError('No warehouse found.');
        }

        return $this->handleResponse($data, 'Data retrieved successfully.');
    }

    public function getBGFromMega(Request $request)
    {
        $data = DB::connection('sqlsrv_mega_wms_exim')
            ->table('MBSG_TBL')
            ->select('MBSG_BSGRP', DB::raw("CONCAT(RTRIM(MBSG_BSGRP), ' (', RTRIM(MBSG_DESC), ')' ) AS MBSG_DESC"))
            ->when($request->has('filters'), function ($query) use ($request) {
                foreach ($request->filters as $key => $valueFilter) {
                    $query->where($valueFilter['cols'], $valueFilter['param'], $valueFilter['value']);
                }
            })
            ->get();

        if ($data->isEmpty()) {
            return $this->handleError('No bg Found.');
        }

        return $this->handleResponse($data, 'Data retrieved successfully.');
    }

    public function getDOFromMegaWMS(Request $request)
    {
        $data = DB::connection('sqlsrv_mega_wms_exim')
            ->table('WDEL_TBL')
            ->select(
                'WDEL_DONO',
                'WDEL_KITTY',
                'WDEL_CUSCD',
                'WDEL_CURCD',
                'WDEL_DELCD',
                'WDEL_WHSCD',
                'WDEL_BSGRP',
            )
            ->when($request->has('filters'), function ($query) use ($request) {
                foreach ($request->filters as $filter) {
                    $query->where($filter['cols'], $filter['param'], $filter['value']);
                }
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('WPCK_TBL')
                    ->whereColumn('WPCK_TBL.WPCK_DONO', 'WDEL_TBL.WDEL_DONO')
                    ->whereColumn('WPCK_TBL.WPCK_WHSCD', 'WDEL_TBL.WDEL_WHSCD');
            })
            ->whereNotNull('WDEL_DONO')
            ->whereNotNull('WDEL_STSFG')
            ->groupBy(
                'WDEL_DONO',
                'WDEL_KITTY',
                'WDEL_CUSCD',
                'WDEL_CURCD',
                'WDEL_DELCD',
                'WDEL_WHSCD',
                'WDEL_BSGRP',
            )
            ->get();

        if ($data->isEmpty()) {
            return $this->handleError('No data found for the given user ID.');
        }

        return $this->handleResponse($data, 'Data retrieved successfully.');
    }

    // Render a stored label template (SBPL/ZPL) from gencode, filling {field}
    // placeholders with the values sent from the mobile app.
    public function renderLabel(Request $request)
    {
        $templateId = $request->template_id;
        $list = $request->list ?? [];

        $getTemplate = $this->getDataGencode(
            $templateId,
            [],
            ['template' => 'pgm_value|string'],
            [],
            true
        );

        $template = $getTemplate['template'] ?? '';

        // Replace {field} placeholders with the supplied values.
        $template = preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function ($matches) use ($list) {
            return $list[$matches[1]] ?? '';
        }, $template);

        return $this->handleResponse($template, 'Label rendered!');
    }

    // List available label templates from gencode. By default returns records
    // whose pgm_code start with "SBPL_TEMPLATE"; override with ?prefix=...
    // Each record includes: code, name (pgm_desc), template (pgm_value) and
    // config (pgm_value2, a JSON config for the dynamic data screen).
    public function listLabels(Request $request)
    {
        $prefix = $request->prefix ?? 'SBPL_TEMPLATE';

        $records = \App\Models\PORTAL\PortalGencode::where('pgm_code', 'like', $prefix . '%')
            ->orderBy('pgm_code')
            ->get()
            ->map(function ($row) {
                $rawConfig = $row->pgm_value2;
                $config = null;
                if ($rawConfig) {
                    $decoded = json_decode($rawConfig, true);
                    $config = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $rawConfig;
                }
                return [
                    'code' => $row->pgm_code,
                    'name' => $row->pgm_desc ?: $row->pgm_code,
                    'template' => $row->pgm_value,
                    'config' => $config,
                ];
            });

        return $this->handleResponse($records, 'Labels found!');
    }

    public function ZPLGenerate(Request $request)
    {
        $getTemplate = $this->getDataGencode(
            'BC_TEMPLATE',
            [
                'pgm_value' => $request->template_id,
            ],
            [
                'type' => 'pgm_value|string',
                'template' => 'pgm_value2|string',
                'desc' => 'pgm_desc|string',
            ],
            [],
            true
        );

        $template = $getTemplate['template'];

        $pattern = '/\{(\d+)\}/';
        $template = preg_replace_callback($pattern, function ($matches) use ($request) {
            $id = $matches[1];
            return $this->translateGencode([
                'id' => $id,
                'code' => 'EXIM_BARCODE',
                'list' => $request->list,
            ], true);
        }, $template);

        return $template;
    }
}
