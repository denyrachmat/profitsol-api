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

        if($request->has('filter')){
            foreach ($request->filter as $key => $value) {
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

        return response()->json($data->get()->map(function ($item) {
            return [
                'ID' => $item->ID,
                'PROG' => $item->PROG,
                'SHPREFNO' => $item->SHPREFNO,
                'ITMCD' => $item->ITMCD,
                'SPTNO' => $item->SPTNO,
                'ITMD1' => $item->ITMD1,
                'RCVQT' => $item->RCVQT,
                'ACTSPQ' => $item->ACTSPQ,
                'LBLCOUNT' => $item->LBLCOUNT,
                'RCVDT' => date('d M Y', strtotime($item->RCVDT)),
                'SHPINVNO' => $item->SHPINVNO,
                'SUPTAXINV' => $item->SUPTAXINV,
                'MAKERNM' => $item->MAKERNM,
                'PONO' => $item->PONO,
                'CASENO' => $item->CASENO,
                'BARCODE_VALUE' => json_encode([
                    'ITEMCODE'  => $item->ITMCD,
                    'MAKERPN'   => $item->SPTNO,
                    'ITEMDESC'  => $item->ITMD1,
                    'RCVQTY'    => $item->RCVQT,
                    'SPQ'       => $item->ACTSPQ,
                    'RCVDT'     => $item->RCVDT,
                    'SHPINVNO'  => $item->SHPINVNO,
                    'SUPTAXINV' => $item->SUPTAXINV,
                    'MAKERNM'   => $item->MAKERNM,
                    'PONO'      => $item->PONO,
                    'PRNTDT'    => $item->PRNTDT ?? date('Y-m-d H:i:s'),
                ]),
            ];
        }));
    }
}
