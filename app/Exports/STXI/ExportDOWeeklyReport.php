<?php

namespace App\Exports\STXI;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;

class ExportDOWeeklyReport implements FromCollection, WithHeadings, WithEvents
{
    use RegistersEventListeners, Exportable;

    function __construct($data)
    {
        $this->data = $data;
    }

    public function headings(): array
    {
        return [
            ['#KONTROL PO MINGGUAN'],
            [],
            [
                'PIC',
                'Order to Code',
                'Order code',
                'Part No',
                'Part Name',
                'Purchase order no',
                'Order Issue Date',
                'Ret due date',
                'Order QTY (purch)',
                'Receive Qty (purch)',
                'Order Left Qty (purch)',
                'Plan Delivery Date',
                'CPO Qty',
                'Plan Delivery Qty',
                'Balance Qty',
                'Keterangan'
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
            if (
                $key > 0 &&
                ($value['PART_NO'] !== $this->data[$key - 1]['PART_NO']
                    && $value['PO_NUM'] !== $this->data[$key - 1]['PO_NUM']
                    && $value['PLAN_DATE'] !== $this->data[$key - 1]['PLAN_DATE']
                )
            ) {
                $hasil[] = [
                    'PIC' => $value['PIC'],
                    'ORDER_CODE' => $value['ORDER_CODE'],
                    'STX' => $value['STX'],
                    'PART_NO' => $value['PART_NO'],
                    'PART_NAME' => $value['PART_NAME'],
                    'PO_NUM' => $value['PO_NUM'],
                    'ORD_DATE' => $value['ORD_DATE'],
                    'RET_DATE' => $value['RET_DATE'],
                    'ORD_QTY' => $value['ORD_QTY'],
                    'RCV_QTY' => $value['RCV_QTY'],
                    'ORD_LEFT_QTY' => $value['ORD_LEFT_QTY'],
                    'PLAN_DATE' => $value['PLAN_DATE'],
                    'CPO_QTY' => $value['CPO_QTY'],
                    'PLAN_DLV_QTY' => $value['PLAN_DLV_QTY'],
                    'BAL_QTY' => $value['BAL_QTY'],
                ];
            } else {
                $hasil[] = [
                    'PIC' => NULL,
                    'ORDER_CODE' => NULL,
                    'STX' => NULL,
                    'PART_NO' => NULL,
                    'PART_NAME' => NULL,
                    'PO_NUM' => NULL,
                    'ORD_DATE' => NULL,
                    'RET_DATE' => NULL,
                    'ORD_QTY' => NULL,
                    'RCV_QTY' => NULL,
                    'ORD_LEFT_QTY' => NULL,
                    'PLAN_DATE' => $value['PLAN_DATE'],
                    'CPO_QTY' => NULL,
                    'PLAN_DLV_QTY' => $value['PLAN_DLV_QTY'],
                    'BAL_QTY' => $value['BAL_QTY'],
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

                $event->sheet->getStyle('A1:A1')->applyFromArray([
                    'font' => [
                        'size' => '12',
                        'bold' => true
                    ]
                ]);

                $event->sheet->getStyle('A2:' . $highestColumn . '2')->applyFromArray([
                    'font' => [
                        'size' => '12',
                        'bold' => true
                    ]
                ]);
            }
        ];
    }
}
