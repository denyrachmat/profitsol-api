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

class trainingRecapValue implements FromCollection, WithHeadings, WithEvents, WithStartRow, WithTitle
{
    use Exportable, RegistersEventListeners;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function startRow(): int
    {
        return 8;
    }
    
    public function title(): string
    {
        return 'Recapitulation';
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $hasil = [];

        foreach ($this->data['detail'] as $key => $value) {
            $keyAns = [];
            foreach ($this->data['content_detail'] as $keyDet2 => $valueDet2) {
                if (!empty($valueDet2['div_relation'])) {
                    $userAnswerList = array_values(array_filter($valueDet2['div_relation']['form_hist'], function ($fDet) use ($value) {
                        return $fDet['form_hist_username'] === $value['username'];
                    }));

                    $ansz = [];
                    foreach ($userAnswerList as $keyAnsDet => $valueAns) {
                        $cekJawaban = array_values(array_filter($valueDet2['div_relation']['form_answers_det'], function ($f4) use ($valueAns) {
                            // return $f4['ans_val'] === json_decode($valueAns['form_hist_value']);
                            return in_array($f4['ans_val'], json_decode($valueAns['form_hist_value']));
                        }));

                        if (count($cekJawaban) > 0) {
                            $ansz[] = $valueAns['form_hist_value'];
                        }
                    }

                    $keyAns[] = [
                        'rightAnswer' => count($ansz),
                        'totalAnswer' => count($valueDet2['div_relation']['form_answers_det']),
                        'val' => $ansz,
                        'logger' => $userAnswerList,
                    ];
                }
            }

            $totalHasil = [];
            $totalnya = 0;
            foreach ($keyAns as $keyAnsTotal => $valueAnsTotal) {
                $totalHasil[] = $valueAnsTotal['rightAnswer'] > 0 ? ($valueAnsTotal['rightAnswer'] / $valueAnsTotal['totalAnswer']) * 100 : '0';
                $totalnya += $valueAnsTotal['rightAnswer'] > 0 ? ($valueAnsTotal['rightAnswer'] / ($valueAnsTotal['totalAnswer'])) * 100 : 0;
            }

            $totalHasilValue = $totalnya / count(array_values(array_filter($this->data['content_detail'], function ($f) {
                return !empty($f['div_relation']);
            })));

            $hasil[$key] = array_merge([
                $key + 1,
                $value['users']['first_name'] . ' ' . $value['users']['last_name']
            ], $totalHasil, [number_format($totalHasilValue, 2, '.', ',')]);
        }

        return collect($hasil);
    }

    public function headings(): array
    {
        $filterFormOnly = array_values(array_filter($this->data['content_detail'], function ($f) {
            return !empty($f['div_relation']);
        }));

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
                'NAME',
                'FORM ANSWERS'
            ],
            []
        ];

        $count = 0;

        foreach ($filterFormOnly as $key => $value) {
            $kolom[6][$key] = $key + 1;
        }

        $kolom[6] = array_merge([[], []], $kolom[6]);

        $kolom[6] = array_merge($kolom[6], ['TOTAL']);

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

                $event->sheet->getStyle('A4:G4')->applyFromArray([
                    'font' => [
                        'bold' => true
                    ]
                ]);

                $highestRow = $event->sheet->getHighestRow();
                $highestColumn = $event->sheet->getHighestColumn();

                $event->sheet->styleCells(
                    'A6:G6',
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

                $event->sheet->getStyle('A1:' . $highestColumn . '2')->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle('A6:' . $highestColumn . '7')->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle('C6')->getAlignment()->setHorizontal('center');

                $event->sheet->getDelegate()->mergeCells('C6:' . $highestColumn . '6');

                $event->sheet->getDelegate()->mergeCells('A1:' . $highestColumn . '1');
                $event->sheet->getDelegate()->mergeCells('A2:' . $highestColumn . '2');

                $event->sheet->getDelegate()->mergeCells('A4:B4');
                $event->sheet->getDelegate()->mergeCells('C4:F4');

                $event->sheet->getDelegate()->mergeCells('A6:A7');
                $event->sheet->getDelegate()->mergeCells('B6:B7');
            },
        ];
    }
}
