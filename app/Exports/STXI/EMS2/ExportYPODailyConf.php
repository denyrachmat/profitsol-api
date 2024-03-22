<?php

namespace App\Exports\STXI\EMS2;

use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonPeriod;
class ExportYPODailyConf implements FromCollection
{
    public $fdate, $ldate;
    function __construct($fdate, $ldate){
        $this->fdate = $fdate;
        $this->ldate = $ldate;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $period = CarbonPeriod::create($this->fdate, $this->ldate);
        $hasil = [];

        foreach ($period as $key => $valuePeriod) {
            foreach ($this->getData($valuePeriod->format('Y-m-d')) as $key => $valData) {
                # code...
            }
        }
    }

    function getData($date){
        $data = DB::connection('sqlsrv_mega_exim')->table('Z_STXI_YPO_DAILY_CONF')
        ->where('KSHP_BSGRP', 'SME3IIZMRI')
        ->where('KSHP_SHPDT', $date)
        ->get();

        return $data;
    }
}
