<?php

namespace App\Exports\STXI;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;

class ExportDOFifo implements FromCollection, WithHeadings, WithEvents, WithTitle
{
    use RegistersEventListeners, Exportable;

    private $data;
    
    /**
     * @return string
     */
    public function title(): string
    {
        return 'FIFO List';
    }

    public function __construct($data, $date)
    {
        $this->data = $data;
        $this->date = $date;
    }

    public function headings(): array
    {
        return [
            [
                'Plan Delivery Tanggal '.date('d M Y', strtotime($this->date))
            ],
            [
                'No',
                'Model',
                'Description',
                'Qty Delivery',
                // 'Barcode Remarks',
                'Delivery No',
                'Qty',
                'Box Count',
                'Total Qty'
            ]
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $hasil = [];

        foreach ($this->data as $key => $value) {
            if (count($value['SPQ_FET']) > 0) {
                foreach ($value['SPQ_FET'] as $keyDet => $valueDet) {
                    $hasil[] = [
                        'no' => $keyDet == 0 ? $key + 1 : '',
                        'MITM_MODELCD'=> $keyDet == 0 ? $value['MITM_MODELCD'] : '',
                        'MITM_ITMD1'=> $keyDet == 0 ? $value['MITM_ITMD1'] : '',
                        'QTY'=> $keyDet == 0 ? $value['TOT_OUT_BC_DLV'] + $value['TOT_OUT_STOCK_DLV'] : '',
                        // 'BARCODE_ITER'=> $keyDet == 0 || $valueDet['BARCODE_REMARKS'] != $value['SPQ_FET'][$keyDet - 1]['BARCODE_REMARKS'] ? $valueDet['BARCODE_REMARKS'] : '',
                        'DRD_DELNO'=> $keyDet == 0 || $valueDet['DRD_DELNO'] != $value['SPQ_FET'][$keyDet - 1]['DRD_DELNO'] ? $valueDet['DRD_DELNO'] : '',
                        'DRD_QTY'=> $valueDet['DRD_QTY'],
                        'COUNT_BOX' => $valueDet['BOX_COUNT'],
                        'TOTAL' => $valueDet['DRD_QTY'] * $valueDet['BOX_COUNT']
                    ];
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
                        'size' => '15',
                        'bold' => true
                    ]
                ]);

                $event->sheet->getStyle('A2:'.$highestColumn.'2')->applyFromArray([
                    'font' => [
                        'size' => '12',
                        'bold' => true
                    ]
                ]);

                $event->sheet->getStyle('A2:'.$highestColumn.'2')->getAlignment()->setHorizontal('center');

                $event->sheet->styleCells(
                    'A2:'.$highestColumn.$highestRow,
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

                $event->sheet->getDelegate()->mergeCells('A1:'.$highestColumn.'1');

                $event->sheet->getStyle('G5:'.$highestColumn.$highestRow)->getAlignment()->setHorizontal('right');
            }
        ];
    }
}
