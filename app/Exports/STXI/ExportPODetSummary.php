<?php

namespace App\Exports\STXI;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;

class ExportPODetSummary implements FromCollection, WithEvents, WithHeadings
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
        $data = [
            'No',
            'YEID PART CODE',
            'MAKER P/N',
            'DESCRIPTION',
            'MAKER NAME',
            'SUPPLIER NAME',
        ];
        foreach ($this->getListDate() as $key => $value) {
            $data[] = date('d M Y', strtotime($value));
        }

        $data[] = 'Total per Item';

        return [
            [
                '1st Bucket '.date('M Y', strtotime($this->date)),
            ],
            [
                'Recd: '
            ],
            [],
            $data
        ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $hasil = [];

        $dataPerDateTot = [];
        foreach ($this->data as $key => $value) {
            $dataDate = [];
            $totalPerItem = 0;
            foreach ($this->getListDate() as $keyDate => $valueDate) {
                $dataDate[date('d M Y', strtotime($valueDate))] = isset($value[$valueDate]) ? number_format($value[$valueDate], 0, ".", ",") : 0;
                $totalPerItem += (int)$value[$valueDate];

                $dataPerDateTot[date('d M Y', strtotime($valueDate))][trim($value['item_code'])] = (int)$value[$valueDate];
            }

            $hasil[] = array_merge([
                (int)$key + 1,
                trim($value['item_code']),
                trim($value['item_spt']),
                trim($value['item_desc']),
                trim($value['item_maker']),
                trim($value['sup_name'])
            ], $dataDate, [
                number_format($totalPerItem, 0, ".", ",")
            ]);
        }

        $totalperDate = [];
        foreach ($dataPerDateTot as $keyDate2 => $valueDate2) {
            $hasilTotItem = 0;
            foreach ($valueDate2 as $keyItem => $valueItem) {
                $hasilTotItem += (int)$valueItem;
            }

            $totalperDate[$keyDate2] = number_format($hasilTotItem, 0, ".", ",");
        }

        $totalRows = array_merge([
            0 => 'Total per Date',
            1 => '',
            2 => '',
            3 => '',
            4 => '',
            5 => '',
        ],  $totalperDate);

        $hasilFinal = array_merge($hasil, [$totalRows]);

        logger(json_encode($dataPerDateTot));
        // logger(json_encode($hasilFinal));

        return collect($hasilFinal);
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

                $event->sheet->getStyle('A4:'.$highestColumn.'4')->applyFromArray([
                    'font' => [
                        'size' => '12',
                        'bold' => true
                    ]
                ]);

                $event->sheet->getStyle('A4:'.$highestColumn.'4')->getAlignment()->setHorizontal('center');

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

                // $event->sheet->getDelegate()->mergeCells('A1:B1');
                // $event->sheet->getDelegate()->mergeCells('A2:B2');

                // $event->sheet->getDelegate()->mergeCells('A4:A5');
                // $event->sheet->getDelegate()->mergeCells('B4:B5');
                // $event->sheet->getDelegate()->mergeCells('C4:C5');
                // $event->sheet->getDelegate()->mergeCells('D4:D5');
                // $event->sheet->getDelegate()->mergeCells('E4:E5');
                // $event->sheet->getDelegate()->mergeCells('F4:F5');

                // $event->sheet->getDelegate()->mergeCells('M4:M5');
                // $event->sheet->getDelegate()->mergeCells('N4:N5');
                // $event->sheet->getDelegate()->mergeCells('O4:O5');

                $event->sheet->getStyle('G5:'.$highestColumn.$highestRow)->getAlignment()->setHorizontal('right');
            }
        ];
    }

    public function getListDate()
    {
        $aDates = array();
        $oStart = new \DateTime($this->date);
        $oEnd = clone $oStart;
        $oEnd->add(new \DateInterval("P1M"));

        while ($oStart->getTimestamp() < $oEnd->getTimestamp()) {
            $aDates[] = $oStart->format('Y-m-d');
            $oStart->add(new \DateInterval("P1D"));
        }

        return $aDates;
    }
}
