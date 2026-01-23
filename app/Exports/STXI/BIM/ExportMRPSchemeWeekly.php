<?php

namespace App\Exports\STXI\BIM;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ExportMRPSchemeWeekly implements FromCollection, WithHeadings, WithEvents
{
    use Exportable, RegistersEventListeners;

    private $data, $typeMRP, $addBGLT;

    public function __construct($data, $typeMRP, $addBGLT = 49)
    {
        $this->data = $data;
        $this->typeMRP = $typeMRP;
        $this->resultDate = [];
        $this->resultWeeks = [];
        $this->BGLT = $addBGLT;
        $this->dataPerlist = [];
        $this->dataPerListOriginal = [];
        $this->listLT = [];
    }

    public function headings(): array
    {

        $firstDate = $this->data['first_date'];
        $weekCount = $this->data['week_count'] ?? 98;

        $lastDate = (new \DateTime($firstDate))->modify('+' . ($weekCount * 7 - 1) . ' days')->format('Y-m-d');
        $lt = $this->data['lt'] ?? 21;
        $firstDayOfMonth = (new \DateTime($firstDate))->modify('first day of this month')->format('Y-m-d');
        $dataHeaders = $this->getData($firstDayOfMonth, $lastDate, $lt);

        // $getLeadTimeList = DB::connection('sqlsrv_mega_sme')->table('MITM_TBL')->select('MITM_ETALT')->distinct()->where('MITM_ETALT', '>', 0)->get()->toArray();
        $getLeadTimeList = $this->getListLT();

        // logger("LeadTimeList", $getLeadTimeList);
        // logger("dataHeaders", $dataHeaders);

        $listDataPerLTMega = [];
        foreach ($getLeadTimeList as $key => $valueLT) {
            $this->listLT[] = $valueLT['lt_(days)'];
            $fDateLT = $this->data['po_rel_date'];
            $lastDatePerLT = (new \DateTime($fDateLT))->modify('+' . ((int) ($valueLT['lt_(days)'] + $this->BGLT) + 1) . ' days')->format('Y-m-d');
            $lastDatePerLTOriginal = (new \DateTime($this->data['po_rel_date']))->modify('+' . ((int) ($valueLT['lt_(days)']) + 1) . ' days')->format('Y-m-d');
            $getDateData = $this->getData($firstDayOfMonth, $lastDatePerLT);

            if (!empty($getDateData)) {
                $listDataPerLTMega[(int) $valueLT['lt_(days)']] = $this->getData($firstDayOfMonth, $lastDatePerLT);
                $this->dataPerListOriginal[(int) $valueLT['lt_(days)']] = $this->getData($this->data['po_rel_date'], $lastDatePerLTOriginal);
            }
        }
        logger("listLT", $this->listLT);
        logger("dataPerListOriginal", $this->dataPerListOriginal);

        $this->dataPerlist = $listDataPerLTMega;

        $listWStr = [];
        $getDate = [];
        $getMonthYears = [];
        $realWeeks = [];
        foreach ($dataHeaders as $weekIndex => $weekDate) {
            $listWStr[] = "W" . ($weekIndex + 1);
            $realWeeks[] = $weekIndex + 1;
            $getDate[] = date("d", strtotime($weekDate));
            $getMonthYears[] = $weekIndex > 0
                ? (
                    date('M Y', strtotime($dataHeaders[$weekIndex - 1])) != date("M Y", strtotime($weekDate))
                    ? date("M Y", strtotime($weekDate))
                    : ''
                )
                : date("M Y", strtotime($weekDate));
        }

        $this->resultDate = $getMonthYears;
        $this->resultWeeks = $realWeeks;

        $headers = [
            [
                'New MRP scheme (weekly base)'
            ],
            [
                $this->typeMRP == 1 ? '1st' : '2nd' . ' MRP'
            ],
            [
                'MRP Date',
                ':',
                !empty($this->data) ? date('d/m/Y', strtotime($this->data['mrp_date'])) : ''
            ],
            [
                'PO Issue Date',
                ':',
                !empty($this->data) ? date('d/m/Y', strtotime($this->data['first_date'])) : ''
            ],
            [
                'Release P/O date',
                ':',
                !empty($this->data) ? date('d/m/Y', strtotime($this->data['po_rel_date'])) : ''
            ],
            [
                'MRP Cut off',
                ':',
                !empty($this->data) ? date('d/m/Y', strtotime($this->data['mrp_cutoff_date'])) : ''
            ],
            [
                ''
            ],
            array_merge([
                'PART LT',
                '',
                '',
            ], $getMonthYears),
            array_merge([
                'Week',
                'Days',
                '',
            ], $listWStr),
            array_merge([
                'PO Due Date',
                'MegaEMS',
                ''
            ], $getDate)
        ];

        return $headers;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $hasil = [];

        $startKeys = 0;
        $startKeysRows = 0;

        logger("resultDate", $this->resultDate);
        foreach ($this->dataPerlist as $key => $value) {
            $hasil[$startKeysRows][$startKeys] = '';
            $startKeys = $startKeys + 1;
            $hasil[$startKeysRows][$startKeys] = (string) $key;
            $startKeys = $startKeys + 1;
            $hasil[$startKeysRows][$startKeys] = '';
            $startKeys = $startKeys + 1;

            foreach ($this->resultDate as $keyContent => $valueContent) {
                $hasil[$startKeysRows][$startKeys] = isset($value[$keyContent]) ? 1 : '-';
                $startKeys = $startKeys + 1;
            }

            // For change first row data using original week list
            foreach ($this->dataPerListOriginal[$key] as $keyOri => $valueOri) {
                if (isset($value[$keyOri])) {
                    $hasil[$startKeysRows][0] = $this->resultWeeks[$keyOri] . ' W';
                }
            }

            $startKeys = 0;
            $startKeysRows = $startKeysRows + 1;

            // For change first row data using original week list
            foreach ($this->dataPerListOriginal[$key] as $keyOri => $valueOri) {
                if (isset($value[$keyOri])) {
                    $hasil[$startKeysRows][0] = date('d/m/Y', strtotime($valueOri));
                }
            }

            $startKeys = 0;
            $startKeysRows = $startKeysRows + 1;
        }

        logger("FinalData", $hasil);

        return collect([$hasil]);
    }

    public function getData($firstDate, $lastDate, $lt = 0)
    {
        $tz = new \DateTimeZone('Asia/Jakarta');
        $start = new \DateTime($firstDate, $tz);
        $end = new \DateTime($lastDate, $tz);

        // cari Senin pertama pada/ setelah $start
        $firstMonday = (clone $start)->modify('monday this week');
        if ($firstMonday < $start) {
            $firstMonday->modify('+1 week');
        }

        // DatePeriod dengan langkah 1 minggu, inklusif sampai $end
        /**
         * Creates a date period that iterates through weeks starting from the first Monday.
         * 
         * The period starts at $firstMonday and increments by 1 week (P1W) intervals.
         * It continues until one day after the $end date (modified with '+1 day').
         * 
         * DatePeriod includes the start date but excludes the end date by default.
         * Since the end is modified to '+1 day', the iteration will include dates up to
         * and including the original $end date.
         * 
         * Note: This creates a weekly iterator based on Monday start dates. If the intent
         * is to include full weeks (Monday-Sunday), ensure $firstMonday is correctly set
         * to a Monday and the $end date calculation accounts for the full week range needed.
         * 
         * @var \DatePeriod $period Collection of dates at weekly intervals
         */
        $period = new \DatePeriod($firstMonday, new \DateInterval('P1W'), (clone $end)->modify('+1 day'));

        $mondays = [];
        foreach ($period as $d) {
            $mondays[] = $d->format('Y-m-d');
        }

        return $mondays;
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
                        'size' => '14',
                        'bold' => true
                    ]
                ]);

                $event->sheet->getStyle('A2:B6')->applyFromArray([
                    'font' => [
                        'size' => '12',
                        'bold' => true
                    ]
                ]);

                $event->sheet->getStyle('A10:' . $highestColumn . '10')->applyFromArray([
                    'font' => [
                        'size' => '12',
                        'bold' => true
                    ],
                    // 'fill' => [
                    //     'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    //     'color' => ['argb' => 'rgb(255,255,0)']
                    // ]
                ]);

                // $event->sheet->getStyle('A3:' . $highestColumn . '3')->getFill()->applyFromArray(['fillType' => 'solid','rotation' => 0, 'color' => ['rgb' => 'fdff0f'],]);
                // $event->sheet->getStyle('L2:' . $highestColumn . '2')->getFill()->applyFromArray(['fillType' => 'solid','rotation' => 0, 'color' => ['rgb' => '7968ff'],]);
    
                // For color based on cell value == '1'
    
                $event->sheet->styleCells(
                    'A9:' . $highestColumn . $highestRow,
                    [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            ],
                        ]
                    ]
                );

                $startRow = 11;
                $startCol = 3;
                foreach ($this->dataPerlist as $key => $value) {
                    foreach ($value as $keyDateDet => $valueDateDet) {
                        $colLetter = $this->toAlpha($startCol);
                        $cellValue = $event->sheet->getCell($colLetter . $startRow)->getValue();

                        if ($cellValue == 1) {
                            $event->sheet->getStyle($colLetter . $startRow)->getFill()->applyFromArray(['fillType' => 'solid', 'rotation' => 0, 'color' => ['rgb' => $this->typeMRP == 1 ? 'fdff0f' : '0000ff'],]);

                            $event->sheet->getCell($colLetter . $startRow)->setValue('');
                        }

                        $startCol++;

                        if (end($value) === $valueDateDet) {
                            $colIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
                            for ($col = $startCol; $col <= $colIndex; $col++) {
                                $colLetter = $this->toAlpha($col);
                                $event->sheet->getCell($colLetter . $startRow)->setValue('');
                            }
                        }
                    }
                    $colLetter = $this->toAlpha($startCol);

                    $event->sheet->getStyle($colLetter . $startRow . ':' . $highestColumn . $startRow)->getFill()->applyFromArray(['fillType' => 'solid', 'rotation' => 0, 'color' => ['rgb' => 'FFA500'],]);

                    $startCol = 3;
                    $startRow = $startRow + 2;
                }
            }
        ];
    }

    public function toAlpha($num)
    {
        for ($r = ""; $num >= 0; $num = intval($num / 26) - 1)
            $r = chr($num % 26 + 0x41) . $r;
        return $r;
    }

    public function getListLT() {
        try {
            $response = Http::timeout(10)->get(env('APP_URL').'/api/mrs/runningReportFromAPI/MRSAPI_69662c0fd6adf');
            
            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            // Log the error if needed
            logger('Error fetching LT list: ' . $e->getMessage());
        }

        return [];
    }
}
