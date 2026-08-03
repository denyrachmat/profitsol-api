<?php

namespace App\Http\Controllers\STXI\LOG;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\STXI\LOG\QRInc;

class WISController extends Controller
{
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
        } else {
            $data->orderByDesc('ID')->limit(10);
        }

        // Expand each item into per-copy rows so the mobile app simply prints
        // each row as one label. Copies = RCVQT / ACTSPQ (ceil for remainders).
        // Each row carries PRINTQTY (qty on this label), COPYNO, COPIES.
        $rows = [];
        foreach ($data->get() as $item) {
            $qty = (int) ($item->RCVQT ?? 0);
            $spq = (int) DB::connection('sqlsrv_wiswms')->table('MITM_ACTSPEC')
                ->where('ITMCD', $item->ITMCD)
                ->first()
                ->ACTSPQ ?? $item->RCVQT;
                
            $copies = $spq > 0 ? (int) ceil($qty / $spq) : 1;
            if ($copies < 1) $copies = 1;

            for ($i = 1; $i <= $copies; $i++) {
                // Last pack may hold the remainder.
                $printQty = ($i === $copies) ? ($qty - (($copies - 1) * $spq)) : $spq;

                $rows[] = [
                    'ID' => $item->ID,
                    'PROG' => $item->PROG,
                    'SHPREFNO' => $item->SHPREFNO,
                    'ITMCD' => $item->ITMCD,
                    'SPTNO' => $item->SPTNO,
                    'ITMD1' => $item->ITMD1,
                    'RCVQT' => (int) $item->RCVQT,
                    'ACTSPQ' => $spq,
                    'LBLCOUNT' => $item->LBLCOUNT,
                    'RCVDT' => date('Y-m-d', strtotime($item->RCVDT)),
                    'SHPINVNO' => $item->SHPINVNO,
                    'SUPTAXINV' => $item->SUPTAXINV,
                    'MAKERNM' => $item->MAKERNM,
                    'PONO' => $item->PONO,
                    'CASENO' => $item->CASENO,
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
                        'PRNTDT'    => $item->PRNTDT ?? date('Y-m-d H:i:s'),
                    ]),
                ];
            }
        }

        return response()->json($rows);
    }
}
