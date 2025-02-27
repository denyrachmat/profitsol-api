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
        $this->currentRow = 0;
        $this->listSelected = [];
    }
    /**
     * @param Collection $collection
     */
    public function model(array $row)
    {
        ini_set("memory_limit", "3G");
        // $countDate = 1;
        if ($this->currentRow > 0) {
            foreach ($row as $key => $value) {
                // if ($key > 12 && $key < 75 && $key % 2 === 0 && !empty($value)) {
                //     $checkDate = date('Y-m', strtotime($this->date)) . '-' . $countDate;

                //     // Jika hari minggu tambah 1 hari ke hari senin
                //     if (date('w', strtotime($checkDate)) == '0') {
                //         $countDate = $countDate + 1;
                //     }

                //     // Jika hari sabtu tambah 2 hari ke hari senin
                //     if (date('w', strtotime($checkDate)) == 6) {
                //         $countDate = $countDate + 2;
                //     }

                //     $date = date('Y-m', strtotime($this->date)) . '-' . $countDate;

                //     if (!empty($value) && !empty($row[0])) {
                //         $item = $row[0];
                //         FRCST_PO_MRI::updateOrCreate([
                //             'FPM_ITMCD' => $this->formatItem($item),
                //             'FPM_UPLDT' => $date,
                //         ], [
                //             'FPM_ITMCD' => $this->formatItem($item),
                //             'FPM_UPLDT' => $date,
                //             'FPM_QTY' => (int) $value,
                //         ]);
                //     }

                //     $countDate++;
                // }

                if ($key > 12) {
                    foreach ($this->listSelected as $keySelHead => $valueSelHead) {
                        $string = $valueSelHead['valueHead'];
                        preg_match_all('/\d+/', $string, $matches);
                        $quantities = $matches[0];

                        if (count($quantities) > 0 && (int) $row[$valueSelHead['keyHead']] > 0) {
                            $countDate = (int) $quantities[0];
                            $checkDate = date('Y-m', strtotime($this->date)) . '-' . $countDate;

                            // Jika hari minggu tambah 1 hari ke hari senin
                            if (date('w', strtotime($checkDate)) == '0') {
                                $countDate = (int) $countDate + 1;
                            }

                            // Jika hari sabtu tambah 2 hari ke hari senin
                            if (date('w', strtotime($checkDate)) == 6) {
                                $countDate = (int) $countDate + 2;
                            }

                            $date = date('Y-m', strtotime($this->date)) . '-' . $countDate;
                            if (!empty($row[0])) {;
                                $item = $row[0];
                                $cekDataPO = FRCST_PO_MRI::where('FPM_ITMCD', $this->formatItem($item))
                                    ->where('FPM_UPLDT', $date)
                                    ->where('FPM_ITMCD', $this->formatItem($item))
                                    ->where('FPM_QTY', (int) $row[$valueSelHead['keyHead']])
                                    ->first();

                                    if (empty($cekDataPO)) {

                                        logger($row[0]. '- Ready to inserted');

                                        FRCST_PO_MRI::create([
                                            'FPM_ITMCD' => $this->formatItem($item),
                                            'FPM_UPLDT' => $date,
                                            'FPM_QTY' => (int) $row[$valueSelHead['keyHead']],
                                        ]);
                                    }
                            }
                        }

                        // $countDate++;
                    }
                }
            }
        } else {
            foreach ($row as $keyHeader => $valueHeader) {
                if (str_contains($valueHeader, 'DayQty')) {
                    $this->listSelected[] = [
                        'keyHead' => $keyHeader,
                        'valueHead' => $valueHeader
                    ];
                }
            }
        }

        $this->currentRow++;
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
        return 1;
    }
}
