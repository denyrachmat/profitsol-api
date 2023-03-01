<?php

namespace App\Exports\STXI;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;

class ExportForcastDLVTYO implements FromCollection, WithHeadings, WithEvents, WithCustomStartCell, WithTitle
{
    use RegistersEventListeners, Exportable;
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'FC';
    }

    public function startCell(): string
    {
        return 'A5';
    }

    public function headings(): array
    {
        $hasilHeaderTanggal = [];
        $hasilDlvFC = [];
        foreach ($this->data as $key => $value) {
            $hasilHeaderTanggal[] = $value['full_date'];
            $hasilHeaderTanggal[] = '';
            $hasilDlvFC[] = 'Forecast';
            $hasilDlvFC[] = 'Delivery';
        }

        return [
            [
                'NO',
                'ORDER_CODE',
                'FORECAST QTY'
            ],
            array_merge(
                [
                    '',
                    ''
                ],
                $hasilHeaderTanggal
            ),
            array_merge(
                [
                    '',
                    ''
                ],
                $hasilDlvFC
            )
        ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $hasil = [];
        $no = 1;
        foreach (array_values($this->data) as $key => $value) {
            // Item Only
            if ($key === 0) {
                foreach ($value['data'] as $keyDet => $valueDet) {
                    $hasil[] = [
                        0 => $no,
                        1 => trim($valueDet->MITM_ITMCD),
                        // 'FDT_QTY_'.$key => $valueDet->FDT_QTY,
                        // 'SSHP_SHPQT_'.$key => $valueDet->SSHP_SHPQT,
                    ];

                    $no++;
                }
            } else {
                foreach ($value['data'] as $keyDet => $valueDet) {
                    $checkExistsItem = array_filter(array_values($hasil), function ($f) use ($valueDet) {
                        return $f[1] === trim($valueDet->MITM_ITMCD);
                    }, ARRAY_FILTER_USE_BOTH);
                    if (count($checkExistsItem) === 0) {
                        $hasil[] = [
                            0 => $no,
                            1 => trim($valueDet->MITM_ITMCD),
                            // 'FDT_QTY_'.$key => $valueDet->FDT_QTY,
                            // 'SSHP_SHPQT_'.$key => $valueDet->SSHP_SHPQT,
                        ];

                        $no++;
                    }
                }
            }
        }

        // Content
        foreach ($hasil as $keyCont => $valueCont) {
            foreach ($this->data as $key2 => $value2) {
                $findItem = array_values(array_filter(json_decode(json_encode($value2['data']), true), function ($f) use ($valueCont) {
                    return trim($f['MITM_ITMCD']) === $valueCont[1];
                }, ARRAY_FILTER_USE_BOTH));

                if (isset($findItem[0]) && count($findItem) > 0) {
                    array_push($hasil[$keyCont], $findItem[0]['FDT_QTY'], $findItem[0]['SSHP_SHPQT']);
                } else {
                    array_push($hasil[$keyCont], '0', '0');
                }
            }
        }
        // logger(json_encode($hasil));
        // logger(json_encode(array_values($this->data)));
        // Total per month
        $start = 2;
        $total = ['Total Per Month', ''];
        $totalCek = ['Total Per Month', ''];
        // Total
        foreach (array_values($this->data) as $key3 => $value3) {
            $totalF = 0;
            $totalA = 0;
            $cekF = [];
            $cekA = [];

            // Rows
            foreach ($hasil as $key => $value) {
                $totalF += $value[$start];
                // $cekF[] = [
                //     'valRow' => $value,
                //     'keyCol' => $key3
                // ];

                $totalA += $value[$start + 1];
                // $cekA[] = [
                //     'valRow' => $value,
                //     'keyCol' => $key3
                // ];
                // $totalF += $hasil[$key3][$key];
                // $totalA += $hasil[$key3][$key + 1];
            }

            array_push($total, (string) $totalF, (string) $totalA);
            array_push($totalCek, $cekF, $cekA);
            $start = $start + 2;
        }

        // logger($totalCek);
        array_push($hasil, $total);
        return collect($hasil);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $highestRow = $event->sheet->getHighestRow();
                $highestColumn = $event->sheet->getHighestColumn();

                $event->sheet->getDelegate()->getPageSetup()
                    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

                $event->sheet->getStyle('A1:A1')->applyFromArray([
                    'font' => [
                        'size' => '12',
                        'bold' => true
                    ]
                ]);

                $event->sheet->getDelegate()->mergeCells('C5:' . $highestColumn . '5');

                $event->sheet->getStyle('A5')->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle('A5')->getAlignment()->setVertical('center');
                $event->sheet->getStyle('B5')->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle('B5')->getAlignment()->setVertical('center');

                $event->sheet->getStyle('C5:' . $highestColumn . '6')->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle('C8:' . $highestColumn . $highestRow)->getAlignment()->setHorizontal('right');
                $event->sheet->getDelegate()->mergeCells('A5:A7');
                $event->sheet->getDelegate()->mergeCells('B5:B7');

                $event->sheet->getStyle('A5:' . $highestColumn . '5')->getFill()->applyFromArray(['fillType' => 'solid','rotation' => 0, 'color' => ['rgb' => 'FFFF33'],]);

                $event->sheet->getStyle('C8:' . $highestColumn . $highestRow)->getNumberFormat()
                    ->setFormatCode(
                            \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                    );

                $event->sheet->getStyle('A5:' . $highestColumn . '7')->applyFromArray([
                    'font' => [
                        'size' => '12',
                        'bold' => true
                    ],
                ]);

                $event->sheet->styleCells(
                    'A5:' . $highestColumn . $highestRow,
                    [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            ],
                        ]
                    ]
                );

                $startCol = 2;
                foreach (array_values($this->data) as $key => $value) {
                    // logger($this->toAlpha($startCol).'6:'.$this->toAlpha($startCol + 1).'6');
                    $event->sheet->getDelegate()->mergeCells($this->toAlpha($startCol) . '6:' . $this->toAlpha($startCol + 1) . '6');
                    $event->sheet->getStyle($this->toAlpha($startCol) . '6:' . $this->toAlpha($startCol + 1) . '6')->getFill()->applyFromArray(['fillType' => 'solid','rotation' => 0, 'color' => ['rgb' => '33AEFF'],]);
                    $startCol = $startCol + 2;
                    // $hasilHeaderTanggal[] = $value['full_date'];
                    // $hasilHeaderTanggal[] = '';
                }

                $event->sheet->getDelegate()->mergeCells('A'.$highestRow.':B'.$highestRow);
                $event->sheet->getStyle('A'.$highestRow.':' . $highestColumn.$highestRow)->getFill()->applyFromArray(['fillType' => 'solid','rotation' => 0, 'color' => ['rgb' => 'AEAEAE'],]);
            }
        ];
    }

    public function toAlpha($num)
    {
        for ($r = ""; $num >= 0; $num = intval($num / 26) - 1)
            $r = chr($num % 26 + 0x41) . $r;
        return $r;
    }
}