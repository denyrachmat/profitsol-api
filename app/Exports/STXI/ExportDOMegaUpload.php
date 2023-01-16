<?php

namespace App\Exports\STXI;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;

class ExportDOMegaUpload implements FromCollection, WithHeadings, WithTitle, WithEvents
{
    use Exportable, RegistersEventListeners;
    private $data;

    public function __construct($data, $date)
    {
        $this->data = $data;
        $this->date = $date;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Upload TYO DO '.$this->date;
    }
    

    public function headings(): array
    {
        return [
            [
                'Costumer Code',
                'TYD261R'
            ],
            [
                'Slip No',
                'Web PO RLS '.date('d-M-y', strtotime($this->date))
            ],
            [
                'Issue Date',
                date('d-M-y', strtotime($this->date))
            ],
            [],
            [
                'ITEM CODE',
                'REQUIRED DATE',
                'ORDER QTY',
                'UNIT PRICE',
                'DELIVERY CODE',
                'CUST. DELIVERY NO'
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
            $hasil[] = [
                'ITEM' => $value['TPM_ITMCD'],
                'REQDT' => $value['TPM_DLVDT'],
                'QTY' => $value['TPM_ORDERQTY'],
                'PRC' => $value['TPM_PRC'],
                'DLVCD' => 'SMT100U',
                'DLVCUST' => $value['TPM_ORDERNO'],
            ];
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
