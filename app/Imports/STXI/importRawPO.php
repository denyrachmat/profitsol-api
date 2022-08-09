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
        ini_set("memory_limit","3G");
        $countDate = 1;
        foreach ($row as $key => $value) {
            if ($key > 12 && $key < 75 && $key % 2 === 0) {
                if (!empty($value)) {
                    $item = $row[0];
                    FRCST_PO_MRI::updateOrCreate([
                        'FPM_ITMCD' => $this->formatItem($item),
                        'FPM_UPLDT' => date('Y-m', strtotime($this->date)).'-'.$countDate,
                    ],[
                        'FPM_ITMCD' => $this->formatItem($item),
                        'FPM_UPLDT' => date('Y-m', strtotime($this->date)).'-'.$countDate,
                        'FPM_QTY' => (int)$value,
                    ]);
                }

                $countDate++;
            }
        }
    }

    public function formatItem($rawItem)
    {
        $splitString = str_split($rawItem);
        if (count($splitString) === 14) {
            $formula = $splitString[0] === '9' ? [5, 5, 2, 2] : [3, 5, 2, 2, 2];
            $resultItem = $this->calcItem($rawItem, $formula);

            return $resultItem;
        }

        return $rawItem;
    }

    public function calcItem($str, $formula)
    {
        $splitString = str_split($str);

        $lastKey = 0;
        $resultItem = '';
        for ($i = 0; $i < count($formula); $i++) {
            for ($j = $lastKey; $j < count($splitString); $j++) {
                $resultItem .= $splitString[$j];
                if ($j === ($formula[$i] - 1) + ($lastKey) && $j !== count($splitString) - 1) {
                    $lastKey = $j + 1;
                    $resultItem .= '-';

                    break;
                }
            }
        }

        return $resultItem;
    }

    public function startRow(): int
    {
        return 2;
    }
}
