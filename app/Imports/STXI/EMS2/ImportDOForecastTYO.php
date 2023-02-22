<?php

namespace App\Imports\STXI\EMS2;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use App\Models\STXI\EMS2\FRCST_DLV_TYO;

class ImportDOForecastTYO implements ToModel, WithStartRow
{
    private $year;

    public function __construct($year)
    {
        $this->year = $year;
        $this->listMonth = [4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2, 3];
    }
    public function startRow(): int
    {
        return 7;
    }

    /**
    * @param Collection $collection
    */
    public function model(array $row)
    {
        ini_set("memory_limit", "3G");

        if (!empty($row[1])) {
            $start = 7;
            foreach ($this->listMonth as $key => $value) {
                FRCST_DLV_TYO::create([
                    'FDT_ITMCD' => $row[1],
                    'FDT_MONTH' => $value,
                    'FDT_YEAR' => ($value === 1 || $value === 2 || $value === 3 ? (int)$this->year + 1 : $this->year),
                    'FDT_QTY' => empty($row[$start]) 
                        ? 0 
                        : (int)$row[$start]
                ]);
            }
        }
    }
}
