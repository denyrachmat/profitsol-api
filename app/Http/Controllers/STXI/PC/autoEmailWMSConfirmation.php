<?php

namespace App\Http\Controllers\STXI\PC;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\STXI\PC\AMAIL_DOPCK_LOG;
use App\Models\STXI\PC\AMAIL_DOPCK_CONF;
use App\Jobs\STXI\PC\autoMailDOPackingListQueue;

class autoEmailWMSConfirmation extends BaseController
{
    public function sendEmailFun()
    {
        $getData = DB::connection('sqlsrv_mega_sme')->table('KSHP_TBL')
            ->select(
                'KSHP_LOCCD',
                'KSHP_BSGRP',
                'KSHP_DONO',
                'KSHP_DELCD'
            )
            ->leftJoin('STXIINTSYS.PC.dbo.AMAIL_DOPCK_LOG', function ($j) {
                $j->on('KSHP_DONO', 'AMDL_DONO');
                $j->on('KSHP_BSGRP', 'AMDL_BSGRP');
                $j->on('KSHP_LOCCD', 'AMDL_LOCCD');
                $j->on('KSHP_DELCD', 'AMDL_DELCD');
            })
            ->where('KSHP_LOCCD', 'PSGL')
            ->whereIn('KSHP_KITTY', ['1', '2'])
            ->whereNull('AMDL_DELCD')
            // ->where('KSHP_DELCD', 'SMT100U')
            ->groupBy(
                'KSHP_LOCCD',
                'KSHP_BSGRP',
                'KSHP_DONO',
                'KSHP_DELCD'
            )
            ->get()
            ->toArray();

        $listConfigEmail = AMAIL_DOPCK_CONF::select(
            'AMDC_BSGRP',
            'AMDC_DELCD'
        )->get()
            ->toArray();

        $getData = array_map(function ($valueDe2) {
            return (array) $valueDe2;
        }, $getData);

        $listDataWillEmail = [];
        foreach ($listConfigEmail as $key => $value) {
            $listData = array_values(array_filter($getData, function ($f) use ($value) {
                return trim($f['KSHP_BSGRP']) == $value['AMDC_BSGRP'] && trim($f['KSHP_DELCD']) == $value['AMDC_DELCD'];
            }));

            if (count($listData) > 0) {
                $listDataWillEmail[$value['AMDC_BSGRP'] . '-' . $value['AMDC_DELCD']] = [
                    'data' => $listData,
                    'email' => AMAIL_DOPCK_CONF::where('AMDC_BSGRP', $value['AMDC_BSGRP'])
                        ->where('AMDC_DELCD', $value['AMDC_DELCD'])
                        ->get()
                        ->toArray()
                    // 'email' => [
                    //     [
                    //         'AMDC_BSGRP' => 'SME1PPZIEP',
                    //         'AMDC_DELCD' => 'IEI',
                    //         'AMDC_EMAIL' => 'deny-rachmat@sumitronics.co.jp',
                    //         'AMDC_EMAILTYPE' => 'to',
                    //     ]
                    // ]
                ];

                foreach ($listData as $key2 => $value2) {
                    AMAIL_DOPCK_LOG::updateOrCreate([
                        "AMDL_LOCCD" => $value2['KSHP_LOCCD'],
                        "AMDL_BSGRP" => $value2['KSHP_BSGRP'],
                        "AMDL_DONO" => $value2['KSHP_DONO'],
                        "AMDL_DELCD" => $value2['KSHP_DELCD'],
                        "AMDL_STAT" => 1,
                    ]);
                }
            }
        }

        $hasil = array_values(array_filter($listDataWillEmail, function ($f) {
            return count($f['data']) > 0;
        }));

        foreach ($hasil as $key => $value) {
            autoMailDOPackingListQueue::dispatch($value)->onQueue('sendEmailQueue');
        }

        return $this->handleResponse($hasil, 'Email queued');
    }
}
