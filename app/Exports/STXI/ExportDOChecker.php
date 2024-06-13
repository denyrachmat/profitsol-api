<?php

namespace App\Exports\STXI;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ExportDOChecker implements FromCollection, WithHeadings, WithEvents
{
    use Exportable, RegistersEventListeners;
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }
    public function headings(): array
    {
        return [
            [
                'PO Checker',
            ],
            [
                'Item Code',
                'Item Desc',
                'Issue Date',
                'Due Date',
                'Order No',
                'Order Qty',
                'Sales Price',
                'Price',
                'SPQ',
                'Sheet',
                'Version',
                'Remark',
                'Due Date (Days)',
                'Status PO on MEGA'
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
                'TPM_ITMCD' => $value['TPM_ITMCD'],
                'MITM_ITMD1' => $value['MITM_ITMD1'],
                'TPM_ISSDT' => Date::PHPToExcel(date('Y-m-d', strtotime($value['TPM_ISSDT']))),
                'TPM_DLVDT' => Date::PHPToExcel(date('Y-m-d', strtotime($value['TPM_DLVDT']))),
                'TPM_ORDERNO' => $value['TPM_ORDERNO'],
                'TPM_ORDERQTY' => $value['TPM_ORDERQTY'],
                'TPM_SLSPRC' => $value['TPM_PRC'],
                'TPM_PRC' => $value['TPM_ORDERQTY'] * $value['TPM_PRC'],
                'SPQ' => $value['SPQ'],
                'SHEET' => $value['SHEET'],
                'TPM_VERSION' => $value['TPM_VERSION'],
                'TPM_REMARK' => $value['TPM_REMARK'],
                'diff_days' => $value['diff_days'],
                'IS_POEXSTS_DESC' => $value['IS_POEXSTS_DESC'],
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

                $event->sheet->getStyle('A2:'.$highestColumn.'2')->applyFromArray([
                    'font' => [
                        'size' => '12',
                        'bold' => true
                    ]
                ]);

                $event->sheet->getStyle('C')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_XLSX15);
                $event->sheet->getStyle('D')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_XLSX15);

                $event->sheet->getStyle('A2:'.$highestColumn.'2')->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle('I3:I' . $highestRow)->getNumberFormat()
                ->setFormatCode(
                        \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                );

                $event->sheet->getStyle('G3:G' . $highestRow)->getNumberFormat()
                ->setFormatCode(
                        \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                );

                $event->sheet->getStyle('H3:H' . $highestRow)->getNumberFormat()
                ->setFormatCode(
                        \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                );

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

                // $event->sheet->getDelegate()->mergeCells('A1:'.$highestColumn.'1');

                // $event->sheet->getStyle('G5:'.$highestColumn.$highestRow)->getAlignment()->setHorizontal('right');
            }
        ];
    }

}
