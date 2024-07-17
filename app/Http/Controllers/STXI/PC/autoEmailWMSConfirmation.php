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
            ->where('KSHP_LOCCD', 'PSGL')
            ->whereIn('KSHP_KITTY', ['1', '2'])
            // ->where('KSHP_DELCD', 'SMT100U')
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
            $listDataWillEmail[$value['AMDC_BSGRP'] . '-' . $value['AMDC_DELCD']] = array_values(array_filter($getData, function ($f) use ($value) {
                return trim($f['KSHP_BSGRP']) == $value['AMDC_BSGRP'] && trim($f['KSHP_DELCD']) == $value['AMDC_DELCD'];
            }));
        }

        autoMailDOPackingListQueue::dispatch($getData, [
            [
                'AMDC_BSGRP' => 'SME1PPZIEP',
                'AMDC_DELCD' => 'IEI',
                'AMDC_EMAIL' => 'deny-rachmat@sumitronics.co.jp',
                'AMDC_EMAILTYPE' => 'to',
            ],
            [
                'AMDC_BSGRP' => 'SME1PPZIEP',
                'AMDC_DELCD' => 'NEXP',
                'AMDC_EMAIL' => 'deny-rachmat@sumitronics.co.jp',
                'AMDC_EMAILTYPE' => 'cc',
            ]
        ])->onQueue('sendEmailQueue');

        return $this->handleResponse($listDataWillEmail, 'Email queued');
    }
}
