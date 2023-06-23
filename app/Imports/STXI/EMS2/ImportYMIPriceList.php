<?php

namespace App\Imports\STXI\EMS2;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\STXI\EMS2\YMI_QUO_TBL;
use Maatwebsite\Excel\Concerns\WithStartRow;

class ImportYMIPriceList implements ToModel, WithStartRow
{
    public function startRow(): int
    {
        return 5;
    }

    public function model(array $row)
    {
        ini_set("memory_limit", "3G");
        if (!empty($row[1])) {
            YMI_QUO_TBL::create([
                'YQMT_QUO_NO' => strval($row[0]),
                'YQMT_ITMCD' => strval($row[1]),
                'YQMT_BP' => floatval($row[6]),
                'YQMT_SP' => floatval($row[7]),
                'YQMT_RATE' => isset($row[11]) ? floatval($row[11]) : 0,
                'YQMT_SP_RPH' => isset($row[12]) ? (int)$row[12] : 0,
                'YQMT_BGNDT' => isset($row[13]) && is_numeric($row[13]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[13])->format('Y-m-d') : NULL,
                'YQMT_ENDDT' => isset($row[14]) && is_numeric($row[14]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[14])->format('Y-m-d') : NULL,
                'YQMT_EFFDT_RMK' => isset($row[15]) ? strval($row[15]) : '',
                'YQMT_MDL_RMK' => isset($row[16]) ? strval($row[16]) : '',
                'YMQT_REMARK' => isset($row[17]) ? strval($row[17]) : '',
                'YMQT_REMARK2' => isset($row[18]) ? strval($row[18]) : '',               
            ]);
        }
    }
}
