<?php

namespace App\Imports\STXI;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\STXI\EMS2\DLVTYOWkRpt;
use Maatwebsite\Excel\Concerns\WithStartRow;

use \DateTime;

class importWeeklyReport implements ToModel, WithStartRow
{
    /**
    * @param Collection $collection
    */
    public function model(array $row)
    {
        if (!empty($row[3])) {
            $dateOrd = DateTime::createFromFormat("m/d/Y" , $row[6]);
            DLVTYOWkRpt::updateOrCreate([
                'ITEM_CODE' => $row[3],
                'PO_NUM' => $row[5],
                'UPLOAD_DATE' => date('Y-m-d')
            ], [
                'ITEM_CODE' => $row[3],
                'PO_NUM' => $row[5],
                'ORDER_DATE' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[6]),
                'DUE_DATE' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[7]),
                'ORDER_QTY' => $row[8],
                'RCV_QTY' => $row[9],
                'PIC' => $row[0],
            ]);
        }
    }

    public function startRow(): int
    {
        return 4;
    }
}
