<?php

namespace App\Exports\STXI;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Illuminate\Support\Facades\DB;

class ExportPOSummary implements FromCollection, WithEvents, WithHeadings
{
    use RegistersEventListeners, Exportable;

    private $data;

    public function __construct($data, $date)
    {
        $this->data = $data;
        $this->date = $date;
    }

    public function headings(): array
    {
        return [
            [
                '1st Bucket '.date('M Y', strtotime($this->date)),
            ],
            [
                'Recd: '
            ],
            [],
            [
                'No',
                'YEID PART CODE',
                'MAKER P/N',
                'DESCRIPTION',
                'MAKER NAME',
                'SUPPLIER NAME',
                '1st Bucket',
                '2nd Bucket',
                'Total',
                '1st Bucket',
                '2nd Bucket',
                'Total',
                date('M Y', strtotime($this->date . '+ 2 months')),
                date('M Y', strtotime($this->date . '+ 3 months')),
                'Total'
            ],
            [
                '',
                '',
                '',
                '',
                '',
                '',
                $this->getDateParse($this->date, 'first_bucket', 'first_date'). ' - '.$this->getDateParse($this->date, 'first_bucket', 'last_date'),
                $this->getDateParse($this->date, 'second_bucket', 'first_date'). ' - '.$this->getDateParse($this->date, 'second_bucket', 'last_date'),
                date('M Y', strtotime($this->date)),
                $this->getDateParse(date('M Y', strtotime($this->date . '+ 1 months')), 'first_bucket', 'first_date'). ' - ' .$this->getDateParse(date('M Y', strtotime($this->date . '+ 1 months')), 'first_bucket', 'last_date'),
                $this->getDateParse(date('M Y', strtotime($this->date . '+ 1 months')), 'second_bucket', 'first_date'). ' - ' .$this->getDateParse(date('M Y', strtotime($this->date . '+ 1 months')), 'second_bucket', 'last_date'),
                date('M Y', strtotime($this->date . '+ 1 months')),
                '',
                '',
                ''
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
                (int)$key + 1,
                trim($value['item_code']),
                trim($value['item_spt']),
                trim($value['item_desc']),
                trim($value['item_maker']),
                trim($value['sup_name']),
                number_format($value['m1a'],0,".", ","),
                number_format($value['m1b'],0,".", ","),
                number_format($value['m1a'] + $value['m1b'], 0, ".", ","),
                number_format($value['m2a'], 0, ".", ","),
                number_format($value['m2b'], 0, ".", ","),
                number_format($value['m2a'] + $value['m2b'], 0, ".", ","),
                number_format($value['m3'], 0, ".", ","),
                number_format($value['m4'], 0, ".", ","),
                number_format($value['m1a'] + $value['m1b'] + $value['m2a'] + $value['m2b'] + $value['m3'] + $value['m4'], 0, ".", ",")
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

                $event->sheet->getStyle('A1:A2')->applyFromArray([
                    'font' => [
                        'size' => '15',
                        'bold' => true
                    ]
                ]);

                $event->sheet->getStyle('A4:'.$highestColumn.'5')->applyFromArray([
                    'font' => [
                        'size' => '12',
                        'bold' => true
                    ]
                ]);

                $event->sheet->getStyle('A4:'.$highestColumn.'5')->getAlignment()->setHorizontal('center');

                $event->sheet->styleCells(
                    'A4:'.$highestColumn.$highestRow,
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

                $event->sheet->getDelegate()->mergeCells('A1:B1');
                $event->sheet->getDelegate()->mergeCells('A2:B2');

                $event->sheet->getDelegate()->mergeCells('A4:A5');
                $event->sheet->getDelegate()->mergeCells('B4:B5');
                $event->sheet->getDelegate()->mergeCells('C4:C5');
                $event->sheet->getDelegate()->mergeCells('D4:D5');
                $event->sheet->getDelegate()->mergeCells('E4:E5');
                $event->sheet->getDelegate()->mergeCells('F4:F5');

                $event->sheet->getDelegate()->mergeCells('M4:M5');
                $event->sheet->getDelegate()->mergeCells('N4:N5');
                $event->sheet->getDelegate()->mergeCells('O4:O5');

                $event->sheet->getStyle('G6:O'.$highestRow)->getAlignment()->setHorizontal('right');
            }
        ];
    }

    public function getDateParse($date, $remarks, $stat)
    {
        $days = $this->getSetDate((int)date('m', strtotime($date)), $remarks)->{$stat};
        $month = date('m', strtotime($date));
        $year = date('y', strtotime($date));

        return date('d M Y', strtotime($year.'-'.$month.'-'.$days));
    }

    public function getSetDate($month, $remarks)
    {
        $data = DB::connection('sqlsrv_ems2')->table('FRCST_PO_DATE_SET')->where('month', $month)->where('remarks', $remarks)->first();

        // $data = array_map(function ($value) {
        //     return (array)$value;
        // }, $data);

        return $data;
    }
}
