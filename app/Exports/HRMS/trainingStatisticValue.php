<?php

namespace App\Exports\HRMS;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithTitle;

class trainingStatisticValue implements FromCollection, WithHeadings, WithEvents, WithStartRow, WithTitle
{
    use Exportable, RegistersEventListeners;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // $hasil = [];

        $keyAns = [];
        $count = 0;
        foreach ($this->data['content_detail'] as $keyDet2 => $valueDet2) {
            if (!empty($valueDet2['div_relation'])) {
                $count++;
                $keyAnsDet = [];
                foreach ($this->data['detail'] as $keyDetPartisipant => $valueDetPartisipant) {
                    // Get History Answers
                    $userAnswerList = array_values(array_filter($valueDet2['div_relation']['form_hist'], function ($fDet) use ($valueDetPartisipant) {
                        return $fDet['form_hist_username'] === $valueDetPartisipant['username'];
                    }));

                    $ansz = [];
                    foreach ($userAnswerList as $keyAnsDetDet => $valueAns) {
                        $cekJawaban = array_values(array_filter($valueDet2['div_relation']['form_answers_det'], function ($f4) use ($valueAns) {
                            // return $f4['ans_val'] === json_decode($valueAns['form_hist_value']);
                            return in_array($f4['ans_val'], json_decode($valueAns['form_hist_value']));
                        }));

                        if (count($cekJawaban) > 0) {
                            $ansz[] = $valueAns['form_hist_value'];
                        }
                    }

                    $keyAnsDet[] = [
                        'rightAnswer' => count($ansz),
                        'totalAnswer' => count($valueDet2['div_relation']['form_answers_det']),
                        'val' => $ansz,
                        'logger' => $userAnswerList,
                    ];
                }

                $totalHasil = [];
                $totalnya = 0;
                foreach ($keyAnsDet as $keyAnsTotal => $valueAnsTotal) {
                    $totalHasil[] = $valueAnsTotal['rightAnswer'] > 0 ? ($valueAnsTotal['rightAnswer'] / $valueAnsTotal['totalAnswer']) * 100 : '0';
                    $totalnya += $valueAnsTotal['rightAnswer'] > 0 ? ($valueAnsTotal['rightAnswer'] / ($valueAnsTotal['totalAnswer'])) * 100 : 0;
                }

                // HASILNYA
                $keyAns[] = [
                    $count,
                    $valueDet2['div_relation']['form_label'],
                    100 - number_format(($totalnya / (count($this->data['detail']) * 100)) * 100, 2, '.', ','),
                    number_format(($totalnya / (count($this->data['detail']) * 100)) * 100, 2, '.', ',')
                ];
            }
        }

        return collect($keyAns);
    }

    public function title(): string
    {
        return 'Statistics Question';
    }

    public function startRow(): int
    {
        return 7;
    }


    public function headings(): array
    {
        $kolom = [
            ['HRMS TRAINING LOGS'],
            ['PT SUMITRONICS INDONESIA'],
            [],
            [
                'TRAINING NAME',
                '',
                ': ' . $this->data['content_detail'][0]['form_name']
            ],
            [],
            [
                'NO',
                'QUESTION',
                'WRONG PERCENTAGE (%)',
                'RIGHT PERCENTAGE (%)'
            ]
        ];

        return $kolom;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A1:A2')->applyFromArray([
                    'font' => [
                        'size' => '15',
                        'bold' => true
                    ]
                ]);

                $event->sheet->getStyle('A4:D4')->applyFromArray([
                    'font' => [
                        'bold' => true
                    ]
                ]);

                $highestRow = $event->sheet->getHighestRow();
                $highestColumn = $event->sheet->getHighestColumn();

                $event->sheet->getStyle('B7:B' . $highestRow)->getAlignment()->setWrapText(true);

                $event->sheet->styleCells(
                    'A6:D6',
                    [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            ],
                        ]
                    ]
                );

                $event->sheet->styleCells(
                    'A6:' . $highestColumn . $highestRow,
                    [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            ],
                        ]
                    ]
                );

                // Caritahu paling besar salah dan betul

                $wrongList = [];
                $rightList = [];
                
                for ($row = 7; $row <= $highestRow; $row++) {
                    $rowData = $event->sheet->rangeToArray(
                        'A' . $row . ':' . $highestColumn . $row,
                        NULL,
                        TRUE,
                        FALSE
                    );

                    $wrongList[] = [
                        'row' => $row,
                        'val' => $rowData[0][2]
                    ];

                    $rightList[] = [
                        'row' => $row,
                        'val' => $rowData[0][3]
                    ];
                }

                $mostWrong = null;
                $wrongCount = 0;
                foreach ($wrongList as $keyWrong => $valueWrong) {
                    if ($valueWrong['val'] > $wrongCount) {
                        $wrongCount = $valueWrong['val'];
                        $mostWrong = $valueWrong;
                    }
                }

                $mostRight = null;
                $rightCount = 0;
                foreach ($rightList as $keyRight => $valueRight) {
                    if ($valueRight['val'] > $rightCount) {
                        $rightCount = $valueRight['val'];
                        $mostRight = $valueRight;
                    }
                }

                if (!empty($mostWrong)) {
                    // Wrong Color
                    $event->sheet->getStyle('C'.$mostWrong['row'])->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ff0000');
                }

                if (!empty($mostRight)) {
                    // Wrong Color
                    $event->sheet->getStyle('D'.$mostRight['row'])->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('05ff00');
                }


                $event->sheet->getStyle('A1:' . $highestColumn . '2')->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle('A6:' . $highestColumn . '6')->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle('C6')->getAlignment()->setHorizontal('center');

                // $event->sheet->getDelegate()->mergeCells('C6:' . $highestColumn . '6');

                $event->sheet->getDelegate()->mergeCells('A1:' . $highestColumn . '1');
                $event->sheet->getDelegate()->mergeCells('A2:' . $highestColumn . '2');

                $event->sheet->getDelegate()->mergeCells('A4:B4');
            },
        ];
    }
}
