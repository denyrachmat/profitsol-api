<?php

namespace App\Imports\STXI;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use App\Models\STXI\EMS2\FRCST_PO_MRI;

class importRawPO implements ToModel, WithStartRow
{
    private $date;

    public function __construct($date)
    {
        $this->date = $date;
    }
    /**
     * @param Collection $collection
     */
    public function model(array $row)
    {
        foreach ($row as $key => $value) {
            if ($key > 12) {
                $item = $row[0][$key];
                FRCST_PO_MRI::create([
                    'FPM_ITMCD' => $this->formatItem($item),
                    'FPM_UPLDT' => $row[0],
                    'FPM_QTY' => $row[0],
                ]);
            }
        }
    }

    public function formatItem($rawItem)
    {
        $splitString = str_split($rawItem);
        if (count($splitString) === 14) {
            if ($splitString[0] === '9') {
                $formula = [5, 5, 2, 2];

                for ($i=0; $i < count($formula); $i++) {
                    # code...
                }
            }
        }

        return $rawItem;
    }

    public function startRow(): int
    {
        return 2;
    }
}
