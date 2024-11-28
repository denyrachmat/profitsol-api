<?php

namespace App\Imports\STXI\BIM;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Events\AfterSheet;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStartRow;

use App\Imports\STXI\BIM\ImportTENListByYear;
use App\Models\STXI\BIM\CircularTenList;
use App\Models\STXI\BIM\CircularTenListModelDet;

class ImportTENList implements ToModel, WithEvents, WithStartRow, WithMultipleSheets
{
    use Importable, RegistersEventListeners;

    public $data, $year, $sheetPos, $storedData;
    public function __construct($year, $sheetPos, $storedData = []) {
        $this->year = $year;
        $this->sheetsKeys = [];
        $this->sheetPos = $sheetPos;
        $this->storedData = $storedData;
    }

    public function sheets(): array
    {
        return [
            $this->sheetPos => $this,
            // 1 => new ImportTENListByYear
        ];
    }

    /**
     * @return int
     */
    public function startRow(): int
    {
        return 3;
    }

    /**
    * @param Collection $collection
    */
    public function model(Array $row)
    {
        ini_set('memory_limit', -1);
        // logger(json_encode($row));
        // if(!array_filter($row)) {
        //     return null;
        //  }

        if (
            !empty($row[1]) && !empty($row[3]) && !empty($row[2])
            // && (
            //     (substr($row[3],0,3) === 'TEN' && substr($row[2],0,3) === 'TEN') ||
            //     (substr($row[3],0,3) === 'N06' && substr($row[2],0,3) === 'N06') ||
            //     (substr($row[3],0,3) === 'N06' && substr($row[2],0,3) === 'TEN')
            // )
        ) {
            $insert = CircularTenList::updateOrCreate([
                'CTT_SECTENNO' => $row[3],
                'CTT_IEITENNO' => $row[2],
            ],[
                'CTT_SUBJECT' => $row[7],
                'CTT_SECTENNO' => $row[3],
                'CTT_IEITENNO' => $row[2],
                'CTT_EMLDT' => isset($row[1]) && is_numeric($row[1]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[1])->format('Y-m-d') : null,
                'CTT_EXCUPDT' => isset($row[11]) && is_numeric($row[11]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[11])->format('Y-m-d') : null,
                'CTT_ITMUPDT' => isset($row[12]) && is_numeric($row[12]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[12])->format('Y-m-d') : null,
                'CTT_BOMUPDT' => isset($row[13]) && is_numeric($row[13]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[13])->format('Y-m-d') : null,
            ]);

            $this->storedData = $insert;

            if ($this->storedData->CTT_IEITENNO == $row[2] || (empty($row[2]) && (!empty($row[8]) || !empty($row[9])))) {
                CircularTenListModelDet::updateOrCreate([
                    'CTT_ID' => $this->storedData->id,
                    'CTID_ITEMCDNEW' => (string)$row[10],
                ], [
                    'CTT_ID' => $this->storedData->id,
                    'CTID_ITEMCDOLD' => (string)$row[9],
                    'CTID_ITEMCDNEW' => (string)$row[10],
                ]);
            }

            // CircularTenListModelDet
        } else {
            if ($this->storedData->CTT_IEITENNO == $row[2] || (empty($row[2]) && (!empty($row[8]) || !empty($row[9])))) {
                CircularTenListModelDet::updateOrCreate([
                    'CTT_ID' => $this->storedData->id,
                    'CTID_ITEMCDNEW' => (string)$row[10],
                ], [
                    'CTT_ID' => $this->storedData->id,
                    'CTID_ITEMCDOLD' => (string)$row[9],
                    'CTID_ITEMCDNEW' => (string)$row[10],
                ]);
            } else {
                return $this->storedData;
            }
        }
    }

    // public static function afterSheet(AfterSheet $event) {
    //     logger($event->getReader());
    // }
}
