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

class ExportYMIPriceList implements FromCollection, WithEvents, WithHeadings
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
            [
                'CD/ CU Summary Price List',
            ],
            [],
            [
                'No',
                'Supplier Code',
                'Supplier Currency',
                'Vendor Name',
                'PO No',
                'Req PO Line',
                'Item Code',
                'Maker Part No',
                'Description',
                'PO Date',
                'ETA Date',
                'End Supplier Price',
                'PO Qty',
                'GIT Date',
                'GIT Qty',
                'GIT Doc No',
                'Purchase Amount (USD)',
                '',
                'SP (USD)',
                'Sales Amount (USD)',
                '',
                'Enjoy Amount (USD)',
                'Remarks'
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
            $hasil[] = [
                'No' => $key + 1,
                'SUPP_CD' => $value->SUPP_CD,
                'SUPP_CURR' => $value->SUPP_CURR,
                'SUPP_NM' => $value->SUPP_NM,
                'PO_NO' => $value->PO_NO,
                'PO_LINE' => $value->PO_LINE,
                'ITEM_CODE' => $value->ITEM_CODE,
                'MK_PART_NO' => $value->MK_PART_NO,
                'ITEM_DESC' => $value->ITEM_DESC,
                'PO_DATE' => Date::PHPToExcel(date('Y-m-d', strtotime($value->PO_DATE))),
                'ETA_DATE' => Date::PHPToExcel(date('Y-m-d', strtotime($value->ETA_DATE))),
                'SUPP_PRICE' => $value->SUPP_PRICE,
                'PO_QTY' => $value->PO_QTY,
                'GIT_DATE' => Date::PHPToExcel(date('Y-m-d', strtotime($value->GIT_DATE))),
                'GIT_QTY' => $value->GIT_QTY,
                'GIT_DOCNO' => $value->GIT_DOCNO,
                'PURC_AMT' => $value->PURC_AMT,
                'FS' => '',
                'YQMT_SP' => $value->YQMT_SP,
                'SALES_AMNT' => $value->SALES_AMNT,
                'FS2' => '',
                'ENJ_AMNT' => $value->ENJ_AMNT < 0 ? $value->ENJ_AMNT * -1 : $value->ENJ_AMNT,
                'REMARKS' => $value->REMARKS,
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

                $event->sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'size' => '18',
                        'bold' => true
                    ]
                ]);

                $event->sheet->getStyle('A3:W3')->applyFromArray([
                    'font' => [
                        'size' => '12',
                        'bold' => true
                    ]
                ]);

                $event->sheet->styleCells(
                    'A3:Q'.$highestRow,
                    [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            ],
                        ]
                    ]
                );

                $event->sheet->styleCells(
                    'S3:T'.$highestRow,
                    [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            ],
                        ]
                    ]
                );

                $event->sheet->styleCells(
                    'V3:W'.$highestRow,
                    [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            ],
                        ]
                    ]
                );

                $event->sheet->getDelegate()->mergeCells('A1:Q1');

                $event->sheet->getStyle('J')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_XLSX15);
                $event->sheet->getStyle('K')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_XLSX15);
                $event->sheet->getStyle('N')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_XLSX15);
                $event->sheet->getStyle('A3:Q3')->getFill()->applyFromArray(['fillType' => 'solid','rotation' => 0, 'color' => ['rgb' => 'FFFF33'],]);
                $event->sheet->getStyle('S3:T3')->getFill()->applyFromArray(['fillType' => 'solid','rotation' => 0, 'color' => ['rgb' => 'FFFF33'],]);
                $event->sheet->getStyle('V3:W3')->getFill()->applyFromArray(['fillType' => 'solid','rotation' => 0, 'color' => ['rgb' => 'FFFF33'],]);
            }
        ];
    }
}