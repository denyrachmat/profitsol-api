<?php

namespace App\Exports\STXI;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
class ExportForcastDLVTYO implements FromCollection, WithHeadings, WithEvents, WithCustomStartCell
{
    use RegistersEventListeners, Exportable;
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
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
                        'NO' => $no,
                        'MITM_ITMCD' => $valueDet->MITM_ITMCD,
                        // 'FDT_QTY_'.$key => $valueDet->FDT_QTY,
                        // 'SSHP_SHPQT_'.$key => $valueDet->SSHP_SHPQT,
                    ];

                    $no++;
                }
            } else {
                foreach ($value['data'] as $keyDet => $valueDet) {
                    $checkExistsItem = array_filter(array_values($hasil), function ($f) use ($valueDet) {
                        return $f['MITM_ITMCD'] == $valueDet->MITM_ITMCD;
                    }, ARRAY_FILTER_USE_BOTH);
                    if (count($checkExistsItem) === 0) {
                        $hasil[] = [
                            'NO' => $no,
                            'MITM_ITMCD' => $valueDet->MITM_ITMCD,
                            // 'FDT_QTY_'.$key => $valueDet->FDT_QTY,
                            // 'SSHP_SHPQT_'.$key => $valueDet->SSHP_SHPQT,
                        ];
    
                        $no++;
                        // $hasil[array_keys($checkExistsItem)[0]] = array_merge(
                        //     $hasil[array_keys($checkExistsItem)[0]],
                        //     [
                        //         'FDT_QTY_'.$key => $valueDet->FDT_QTY,
                        //         'SSHP_SHPQT_'.$key => $valueDet->SSHP_SHPQT,
                        //     ]
                        // );
                    }
                }
            }
        }

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

                $event->sheet->getDelegate()->mergeCells('C5:'.$highestColumn.'5');

                $event->sheet->getStyle('A5')->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle('A5')->getAlignment()->setVertical('center');
                $event->sheet->getStyle('B5')->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle('B5')->getAlignment()->setVertical('center');
                
                $event->sheet->getStyle('C5:'.$highestColumn.'6')->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle('C8:'.$highestColumn.$highestRow)->getAlignment()->setHorizontal('right');
                $event->sheet->getDelegate()->mergeCells('A5:A7');
                $event->sheet->getDelegate()->mergeCells('B5:B7');

                $event->sheet->getStyle('C8:'.$highestColumn.$highestRow)->getNumberFormat()
                ->setFormatCode(
                    \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                );

                $event->sheet->getStyle('A5:' . $highestColumn . '7')->applyFromArray([
                    'font' => [
                        'size' => '12',
                        'bold' => true
                    ],
                ]);

                $startCol = 2;
                foreach (array_values($this->data) as $key => $value) {
                    // logger($this->toAlpha($startCol).'6:'.$this->toAlpha($startCol + 1).'6');
                    $event->sheet->getDelegate()->mergeCells($this->toAlpha($startCol).'6:'.$this->toAlpha($startCol + 1).'6');
                    $startCol = $startCol + 2;
                    // $hasilHeaderTanggal[] = $value['full_date'];
                    // $hasilHeaderTanggal[] = '';
                }
            }
        ];
    }

    
    public function toAlpha($num)
    {
        for($r = ""; $num >= 0; $num = intval($num / 26) - 1)
            $r = chr($num%26 + 0x41) . $r;
        return $r;
    }
}