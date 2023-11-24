<?php

namespace App\Exports\STXI\EMS2;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use \PhpOffice\PhpSpreadsheet\Shared\Date;

class ExportPriceListYMICDCU implements FromCollection, WithEvents, WithHeadings
{
    use RegistersEventListeners, Exportable;
    private $data;
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function headings(): array
    {
        return [
            'No',
            'Item Code',
            'Remarks',
            'Quotation No',
            'BP',
            'SP',
            'DIFF',
            'MU(%)',
            'GP(%)'
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
                'No' => $key + 1,
                'YQMT_ITMCD' => $value->YQMT_ITMCD,
                'YQMT_REMARK' => $value->YQMT_REMARK,
                'YQMT_QUO_NO' => $value->YQMT_QUO_NO,
                'YQMT_BP' => $value->YQMT_BP,
                'YQMT_SP' => $value->YQMT_SP,
                'DIFF' => $value->DIFF,
                'MU' => $value->MU,
                'GP' => $value->GP,
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

                $event->sheet->getStyle('A1:I1')->applyFromArray([
                    'font' => [
                        'size' => '12',
                        'bold' => true
                    ]
                ]);

                $event->sheet->styleCells(
                    'A1:I' . $highestRow,
                    [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            ],
                        ]
                    ]
                );
            }
        ];
    }
}
