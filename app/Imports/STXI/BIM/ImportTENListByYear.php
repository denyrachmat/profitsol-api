<?php

namespace App\Imports\STXI\BIM;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\STXI\BIM\CircularTenList;

class ImportTENListByYear implements ToModel
{
    /**
    * @param Collection $collection
    */
    public function model(Array $row)
    {
        if(!array_filter($row)) {
            return null;
         }

        if (!empty($row[1]) && !empty($row[3]) && !empty($row[2])) {
            return CircularTenList::updateOrCreate([
                'CTT_SECTENNO' => $row[3],
                'CTT_IEITENNO' => $row[2],
            ],[
                'CTT_SECTENNO' => $row[3],
                'CTT_IEITENNO' => $row[2],
                'CTT_EMLDT' => isset($row[1]) && is_numeric($row[1]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[1])->format('Y-m-d') : null,
                'CTT_EXCUPDT' => isset($row[11]) && is_numeric($row[11]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[11])->format('Y-m-d') : null,
                'CTT_ITMUPDT' => isset($row[12]) && is_numeric($row[12]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[12])->format('Y-m-d') : null,
                'CTT_BOMUPDT' => isset($row[13]) && is_numeric($row[13]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[13])->format('Y-m-d') : null,
            ]);
        }
    }
}
