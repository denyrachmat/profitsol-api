<?php

namespace App\Exports\STXI;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use \PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Support\Facades\DB;

use App\Models\STXI\EMS2\YPOForcast;

class ExportYPOManual implements FromCollection, WithEvents, WithHeadings, WithTitle
{
    use RegistersEventListeners, Exportable;
    private $data;
    private $tipe;

    public function __construct($data, $tipe)
    {
        $this->data = $data;
        $this->tipe = $tipe;
        $this->hasil = [];
        $this->getYearData = YPOForcast::select('YFDD_YEAR', 'YFDD_MONTH', DB::raw("CONCAT(YFDD_YEAR, '-',YFDD_MONTH) AS YM"))->groupBy('YFDD_YEAR', 'YFDD_MONTH')->orderBy('YFDD_YEAR')->orderBy('YFDD_MONTH');
    }

    public function title(): string
    {
        return $this->tipe == 'reg' ? 'REGULAR' : 'NEW MODEL';
    }

    public function columnFormats(): array
    {
        return [
            'H' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'I' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'J' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'L' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        date_default_timezone_set('Asia/Jakarta');
        $hasil = [];
        $nomor = 0;
        foreach ($this->data as $key => $value) {
            if ($key === 0 || ($value['YPO_TXID'] === $this->data[$key - 1]['YPO_TXID'])) {
                if ($key === 0) {
                    $hasil[] = [
                        'NO' => $value['YPO_TXID']
                    ];
                    $nomor = 1;
                } else {
                    if ($value['id'] !== $this->data[$key - 1]['id']) {
                        $nomor++;
                    }
                }
            } else {
                $nomor = 1;
                $hasil[] = [
                    'NO' => $value['YPO_TXID']
                ];
            }

            $combData = [
                'NO' => $key > 0 && $value['id'] === $this->data[$key - 1]['id'] ? '' : $nomor,
                'YPO_ITMCD' => $key > 0 && $value['id'] === $this->data[$key - 1]['id'] ? '' : $value['YPO_ITMCD'],
                'MITM_ITMD1' => $key > 0 && $value['id'] === $this->data[$key - 1]['id'] ? '' : $value['MITM_ITMD1'],
                'MITM_SPTNO' => $key > 0 && $value['id'] === $this->data[$key - 1]['id'] ? '' : $value['MITM_SPTNO'],
                'MSUP_ABBRV' => $key > 0 && $value['id'] === $this->data[$key - 1]['id'] ? '' : $value['MSUP_ABBRV'],
                'MSUP_SUPNM' => $key > 0 && $value['id'] === $this->data[$key - 1]['id'] ? '' : $value['MSUP_SUPNM'],
                'YPO_REMARKS' => $key > 0 && $value['id'] === $this->data[$key - 1]['id'] ? '' : $value['YPO_REMARKS'],
                'YPO_MRPDT' => $key > 0 && $value['id'] === $this->data[$key - 1]['id'] || empty($value['YPO_MRPDT']) ? '' : Date::PHPToExcel(date('Y-m-d', strtotime($value['YPO_MRPDT'])))/**Date::PHPToExcel(strtotime($value['YPO_MRPDT']) + 14400)**/ ,
                'YPO_MAILDT' => $key > 0 && $value['id'] === $this->data[$key - 1]['id'] || empty($value['YPO_MAILDT']) ? '' : Date::PHPToExcel(date('Y-m-d', strtotime($value['YPO_MAILDT']))),
                'YPO_RCVDT' => $key > 0 && $value['id'] === $this->data[$key - 1]['id'] || empty($value['YPO_RCVDT']) ? '' : Date::PHPToExcel(date('Y-m-d', strtotime($value['YPO_RCVDT']))),
                'YPO_PONO' => $key > 0 && $value['id'] === $this->data[$key - 1]['id'] ? '' : $value['YPO_PONO'],
                'YPO_PODUEDT' => $key > 0 && $value['id'] === $this->data[$key - 1]['id'] ? '' : $value['YPO_PODUEDT'],
                'YSPDT_POQT' => $value['YSPDT_POQT'],
                '1' => '',
                'YSPDT_PONO' => $value['YSPDT_PONO'],
                'PPO1_ISUDT' => $value['PPO1_ISUDT'],
                'YPO_POQTY' => $key > 0 && $value['id'] === $this->data[$key - 1]['id'] ? '' : ($value['YPO_POQTY'] == 0 ? '-' : $value['YPO_POQTY']),
                'YSPDT_INVNO' => $value['YSPDT_INVNO'],
                'PGIT_RCVDT' => $value['PGIT_RCVDT'],
                'PGRN_RCVDT' => $value['PGRN_RCVDT'],
                'ORI_PGIT_RCVQT' => $value['ORI_PGIT_RCVQT'],
                'PIB_FINISH' => $value['PIB_FINISH'],
                '2' => '',
                'SHP_STAT' => empty($value['PGRN_RCVDT'])
                    ? $value['TOT_QT']
                    : ($value['TOT_QT'] == 0 ? 'CLOSE' : $value['ORI_PGIT_RCVQT'] - $value['SHP_QT']),
                'OS_QTY' => $value['TOT_QT'] == 0 ? '-' : $value['TOT_QT'],
            ];

            $hasil[] = array_merge(
                $combData,
                $this->getData($combData['YPO_ITMCD'], ($key > 0 && $value['id'] === $this->data[$key - 1]['id']))
            );

            // logger($hasil);
        }

        $this->hasil = $hasil;
        return collect($hasil);
    }

    public function headings(): array
    {

        return [
            [
                'STXI - YEID PO CONFIRMATION',
            ],
            [],
            [],
            array_merge(
                [
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    'SUPP INV NO',
                    'GIT DATE',
                    'GRN DATE',
                    'GIT QTY',
                    '',
                    '',
                    'STOCK LEDGER',
                    '',
                    'YEID FORECAST'
                ],
            ),
            array_merge(
                [
                    'NO',
                    'YEID PN',
                    'MAKER PN',
                    'DESCRIPTION',
                    'MAKER NAME',
                    'SUPPLIER NAME',
                    'REMARKS',
                    'YEID PO MRP DATE',
                    'STXI SEND EMAIL TO YEID',
                    'RECEIVE YEID PO',
                    'YEID PO NO',
                    'YEID PO DUE DATE',
                    'YEID PO QTY',
                    '',
                    'STXI PO NO TO SUPPLIER',
                    'STXI ISSUE DATE',
                    'STXI PO QTY',
                    'SUPPLIER INVOICE NO',
                    'SUPPLIER DLV SCHEDULE',
                    'ETA SGL DATE',
                    'SUPPLIER INCOMING QTY',
                    'SHIP DATE',
                    '',
                    'PO SYSTEM YEID',
                    'OS QTY',
                ],
                (clone $this->getYearData)->get()->pluck('YM')->toArray()
            )
        ];
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
                        'size' => '36',
                        'bold' => true
                    ]
                ]);

                $event->sheet->getStyle('A5:' . $highestColumn . '2')->applyFromArray([
                    'font' => [
                        'size' => '11',
                        'bold' => true
                    ]
                ]);

                $event->sheet->styleCells(
                    'R4:U4',
                    [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            ],
                        ]
                    ]
                );

                $event->sheet->styleCells(
                    'A5:M' . $highestRow,
                    [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            ],
                        ]
                    ]
                );

                $event->sheet->styleCells(
                    'O5:V' . $highestRow,
                    [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            ],
                        ]
                    ]
                );

                $event->sheet->styleCells(
                    'X5:'.$highestColumn . $highestRow,
                    [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            ],
                        ]
                    ]
                );

                $event->sheet->getStyle('A5:M5')->getFill()->applyFromArray(['fillType' => 'solid', 'rotation' => 0, 'color' => ['rgb' => '90EE90'],]);
                $event->sheet->getStyle('O5:V5')->getFill()->applyFromArray(['fillType' => 'solid', 'rotation' => 0, 'color' => ['rgb' => 'FFAOAO'],]);

                $event->sheet->getStyle('A4:' . $highestColumn . '5')->getAlignment()->setWrapText(true);
                $event->sheet->getStyle('A4:' . $highestColumn . '5')->getAlignment()->setHorizontal('center');

                // $event->sheet->getStyle('G')->getAlignment()->setWrapText(true);
    
                $event->sheet->getDelegate()->mergeCells('A1:' . $highestColumn . '1');
                $event->sheet->getDelegate()->mergeCells('X4:Y4');
                $event->sheet->getDelegate()->mergeCells('Z4:'.$highestColumn.'4');

                $event->sheet->getStyle('H')
                    ->getNumberFormat()
                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_XLSX15);

                $event->sheet->getStyle('I')
                    ->getNumberFormat()
                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_XLSX15);

                $event->sheet->getStyle('J')
                    ->getNumberFormat()
                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_XLSX15);

                $startRow = 6;
                foreach ($this->hasil as $key => $value) {
                    if (str_contains($value['NO'], 'YPO-')) {
                        // Merge first table
                        $event->sheet->getDelegate()->mergeCells('A' . $startRow . ':M' . $startRow);
                        $event->sheet->getStyle('A' . $startRow . ':M' . $startRow)->getFill()->applyFromArray(['fillType' => 'solid', 'rotation' => 0, 'color' => ['rgb' => '73D2F5'],]);

                        // Merge second table
                        $event->sheet->getDelegate()->mergeCells('O' . $startRow . ':V' . $startRow);
                        $event->sheet->getStyle('O' . $startRow . ':V' . $startRow)->getFill()->applyFromArray(['fillType' => 'solid', 'rotation' => 0, 'color' => ['rgb' => '73D2F5'],]);

                        // Merge third table
                        $event->sheet->getDelegate()->mergeCells('X' . $startRow . ':'.$highestColumn . $startRow);
                        $event->sheet->getStyle('X' . $startRow . ':Y' . $startRow)->getFill()->applyFromArray(['fillType' => 'solid', 'rotation' => 0, 'color' => ['rgb' => '73D2F5'],]);
                    }

                    $startRow++;
                }
            }
        ];
    }

    public function getData($item, $isNull = false): array
    {
        $listFC = [];
        foreach ((clone $this->getYearData)->get() as $key => $value) {
            if ($isNull) {
                $listFC[$value['YFDD_YEAR'] . '-' . $value['YFDD_MONTH']] = 0;
            } else {

                $cekData = YPOForcast::where('YFDD_ITMCD', $item)
                    ->where('YFDD_YEAR', $value['YFDD_YEAR'])
                    ->where('YFDD_MONTH', $value['YFDD_MONTH'])
                    ->first();

                if (!empty($cekData)) {
                    $listFC[$value['YFDD_YEAR'] . '-' . $value['YFDD_MONTH']] = $cekData->YFDD_FCQT;
                } else {
                    $listFC[$value['YFDD_YEAR'] . '-' . $value['YFDD_MONTH']] = 0;
                }
            }
        }

        return $listFC;
    }
}
