<?php

namespace App\Imports\STXI\EMS2;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ImportFCMRI implements ToModel, WithStartRow, WithMultipleSheets
{
    public $data;
    public function __construct() {
        $this->data = null;
        $this->key = 0;
        $this->keyRow = 3;
        $this->yearList = [];
    }

    public function sheets(): array
    {
        return [
            1 => $this,
        ];
    }

    public function startRow(): int
    {
        return 3;
    }

    /**
    * @param Collection $collection
    */
    public function model(array $row)
    {
        date_default_timezone_set('Asia/Jakarta');
        ini_set("memory_limit", "4G");

        $startCol = 7;

        for ($i=0; $i < count($row); $i++) {
            if ($this->keyRow == 3 && is_int($row[$i])) {
                if ($i >= $startCol) {
                    $this->yearList[] = is_int($row[$i]) ? substr($row[$i], 0, 4). '-'.substr($row[$i], 4) : $row[$i];
                }
            } else {
                if ($i === 1 && !empty($row[1])) {
                    $dataPrep = [
                        'item' => $row[$i]
                    ];
                    
                    foreach ($this->yearList as $key => $valueYear) {
                        $this->data[] = array_merge($dataPrep, [
                            'year' => (int)explode('-', $valueYear)[0],
                            'month' => (int)explode('-', $valueYear)[1],
                            'qty' => $row[$startCol + $key]
                        ]);
                    }
                }
            }
        }

        $this->keyRow = $this->keyRow + 1;
    }
}
