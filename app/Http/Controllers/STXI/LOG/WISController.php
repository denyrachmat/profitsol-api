<?php

namespace App\Http\Controllers\STXI\LOG;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\STXI\LOG\QRInc;

use App\Traits\PORTAL\GencodeTraits;

class WISController extends Controller
{
    use GencodeTraits;
    public function filterQRIncData(Request $request)
    {
        $data = QRInc::select(
            'ID',
            'PROG',
            'SHPREFNO',
            'ITMCD',
            'SPTNO',
            'ITMD1',
            'RCVQT',
            'ACTSPQ',
            'LBLCOUNT',
            'RCVDT',
            'SHPINVNO',
            'SUPTAXINV',
            'MAKERNM',
            'PONO',
            'CASENO',
        );

        $filters = $request->input('filter', []);

        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                if (empty($value['value']))
                    continue;

                if($value['op'] == 'between'){
                    $data->whereBetween($value['field'], json_decode($value['value'], true));
                } elseif($value['op'] == 'in'){
                    $data->whereIn($value['field'], json_decode($value['value'], true));
                } elseif($value['op'] == 'like'){
                    $data->where($value['field'], 'like', '%' . $value['value'] . '%');
                } else {
                    $data->where($value['field'], $value['op'], $value['value']);
                }
            }
        }

        // Pagination: returns paginator envelope when { paginate: true, page, rowsPerPage } sent.
        $pages = $request->has('paginate') && $request->paginate != false;

        // Group results by ID and keep per-copy payloads in COPIES_DATA.
        // Each ID appears once, with the copies array carrying PRINTQTY,
        // COPYNO, COPIES, and BARCODE_VALUE for each label.
        $rows = [];

        if ($pages) {
            $paginator = $data->orderByDesc('ID')
                ->paginate((int) ($request->paginate['rowsPerPage'] ?? 10), ['*'], 'page', (int) ($request->paginate['page'] ?? 1));
            $items = $paginator;
        } else {
            $paginator = null;
            $items = $data->orderByDesc('ID')->limit(10)->get();
        }

        foreach ($items as $item) {
            $qty = (int) ($item->RCVQT ?? 0);
            $spq = (int) DB::connection('sqlsrv_wiswms')->table('MITM_ACTSPEC')
                ->where('ITMCD', $item->ITMCD)
                ->first()
                ->ACTSPQ ?? $item->RCVQT;

            $itemDesc = mb_strlen((string) ($item->ITMD1 ?? '')) > 20 ? mb_substr((string) ($item->ITMD1 ?? ''), 0, 20) . '...' : (string) ($item->ITMD1 ?? '');
                
            $copies = $spq > 0 ? (int) ceil($qty / $spq) : 1;
            if ($copies < 1) $copies = 1;

            $groupId = $item->ID;
            if (!isset($rows[$groupId])) {
                $rows[$groupId] = [
                    'ID' => $item->ID,
                    'PROG' => $item->PROG,
                    'SHPREFNO' => $item->SHPREFNO,
                    'ITMCD' => $item->ITMCD,
                    'SPTNO' => $item->SPTNO,
                    'ITMD1' => $itemDesc,
                    'RCVQT' => (int) $item->RCVQT,
                    'ACTSPQ' => $spq,
                    'LBLCOUNT' => $item->LBLCOUNT,
                    'RCVDT' => date('Y-m-d', strtotime($item->RCVDT)),
                    'SHPINVNO' => $item->SHPINVNO,
                    'SUPTAXINV' => $item->SUPTAXINV,
                    'MAKERNM' => $item->MAKERNM,
                    'PONO' => $item->PONO,
                    'CASENO' => $item->CASENO,
                    'COPIES_DATA' => [],
                    'PRINTQTY' => (int) $spq,
                    'COPIES' => $copies,
                ];
            }

            for ($i = 1; $i <= $copies; $i++) {
                // Last pack may hold the remainder.
                $printQty = ($i === $copies) ? ($qty - (($copies - 1) * $spq)) : $spq;
                $rows[$groupId]['COPIES_DATA'][] = [
                    'PRINTQTY' => (int) $printQty,
                    'COPYNO' => $i,
                    'COPIES' => $copies,
                    'BARCODE_VALUE' => json_encode([
                        'ITEMCODE'  => $item->ITMCD,
                        'MAKERPN'   => $item->SPTNO,
                        'ITEMDESC'  => $item->ITMD1,
                        'RCVQTY'    => (int) $item->RCVQT,
                        'SPQ'       => $spq,
                        'RCVDT'     => $item->RCVDT,
                        'SHPINVNO'  => $item->SHPINVNO,
                        'SUPTAXINV' => $item->SUPTAXINV,
                        'MAKERNM'   => $item->MAKERNM,
                        'PONO'      => $item->PONO,
                        'PRNTDT'    => ($item->PRNTDT ?? date('Y-m-d H:i:s')).'.'.sprintf("%06d", (int) $i), // Append milliseconds to PRNTDT
                    ]),
                ];
            }
        }

        if ($paginator) {
            return response()->json([
                'data' => array_values($rows),
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ]);
        }

        return response()->json(array_values($rows));
    }

    public function autocompleteQRIncData(Request $request)
    {
        $field = $request->input('field');
        $q = $request->input('q', '');

        $valid = ['SPTNO','PONO','RCVDT','ITMCD','CASENO','SHPINVNO','SHPREFNO'];
        if (!in_array($field, $valid)) {
            return response()->json([]);
        }

        $data = QRInc::select($field)
            ->whereNotNull($field)
            ->where($field, '!=', '')
            ->orderBy($field);

        if ($q !== '') {
            $data->where($field, 'like', $q . '%');
        }

        $results = $data->distinct()->limit(20)->pluck($field)->filter(function ($v) {
            return $v !== null && $v !== '';
        })->values();

        return response()->json($results);
    }
}
