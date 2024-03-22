<?php

namespace App\Http\Controllers\STXI\EMS2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class YPODailyConfController extends Controller
{
    public function index() {
        $data = DB::connection('sqlsrv_mega_exim')->table('Z_STXI_YPO_DAILY_CONF')
            ->where('KSHP_BSGRP', 'SME3IIZMRI')
            ->where('KSHP_SHPDT', date('Y-m-d'))
            ->get();

        return $data;
    }

    public function store(Request $request) {
        
    }
}
