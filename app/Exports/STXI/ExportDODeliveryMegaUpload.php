<?php

namespace App\Exports\STXI;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;

class ExportDODeliveryMegaUpload implements FromCollection, WithHeadings, WithTitle, WithEvents
{
    use Exportable, RegistersEventListeners;
    private $data;

    public function __construct($data, $WH)
    {
        $this->data = $data;
        $this->WH = $WH;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Upload '.$this->WH;
    }
    

    public function headings(): array
    {
        return [
            [
                'Costumer Code',
                'TYD261R'
            ],
            [
                'Warehouse',
                $this->WH
            ],
            [
                'Delivery Code',
                'SMT100U'
            ],
            [],
            [
                'Item Code',
                'Delivery Date',
                'D/N No',
                'Delivery No',
                'Quantity',
                'Unit Price'
            ],
            []
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $hasil = [];
        foreach ($this->data as $key => $value) {
            foreach ($value['FIFO_DET'] as $keyDet => $valueDet) {
                $hasil[] = [
                    'ITEM' => $valueDet['MITM_MODELCD'],
                    'DLVDT' => $valueDet['DRT_DELDT'],
                    'ID' => $valueDet['DRT_TRANID'],
                    'DN' => $valueDet['DRD_DELNO'],
                    'DQTN' => $valueDet['DRD_QTY'],
                    'DNPRC' => $valueDet['DRD_PRICE']
                ];
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

                // $event->sheet->getStyle('A1:A1')->applyFromArray([
                //     'font' => [
                //         'size' => '15',
                //         'bold' => true
                //     ]
                // ]);

                $event->sheet->getStyle('A5:'.$highestColumn.'5')->applyFromArray([
                    'font' => [
                        'size' => '12',
                        'bold' => true
                    ]
                ]);

                $event->sheet->getStyle('A5:'.$highestColumn.'5')->getAlignment()->setHorizontal('center');

                $event->sheet->styleCells(
                    'A5:'.$highestColumn.$highestRow,
                    [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            ],
                        ]
                    ]
                );

                foreach(range('A', $highestColumn) as $columnID) {
                    $event->sheet->getColumnDimension($columnID)->setAutoSize(true) ;
                }

                // $event->sheet->getDelegate()->mergeCells('A1:'.$highestColumn.'1');

                // $event->sheet->getStyle('G5:'.$highestColumn.$highestRow)->getAlignment()->setHorizontal('right');
            }
        ];
    }
}
