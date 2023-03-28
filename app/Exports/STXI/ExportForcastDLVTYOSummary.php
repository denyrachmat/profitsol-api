<?php

namespace App\Exports\STXI;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithCharts;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;

use App\Models\STXI\EMS2\FRCST_DLV_TYO;
use PhpOffice\PhpSpreadsheet\Chart\Title;

class ExportForcastDLVTYOSummary implements FromCollection, WithHeadings, WithEvents, WithCustomStartCell, WithTitle, WithCharts
{
    use RegistersEventListeners, Exportable;
    private $data;

    public function __construct($data)
    {
        $this->data = json_decode(json_encode($data), true);
        $this->listMonth = [4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2, 3];
    }

    public function charts()
    {
        $label      = [];
        $values     = [];
        $start = 4;
        foreach ($this->data as $key => $value) {
            if ($key === 0 || $value['year_ret'] !== $this->data[$key - 1]['year_ret']) {
                $label[] = new DataSeriesValues('Number', 'Summary!$A$'.$start, null, $key + 1);
                $values[] = new DataSeriesValues('Number', 'Summary!$B$'.$start.':$M$'.$start, null, $key + 2);
                $start++;
            }
        }

        $categories = [new DataSeriesValues('String', 'Summary!$B$3:$M$3', null, $start + 1)];
        // $values     = [new DataSeriesValues('Number', 'Summary!$B$4:$M$6', null, 4)];

        $series = new DataSeries(
            DataSeries::TYPE_LINECHART, 
            DataSeries::GROUPING_STACKED,
            range(0, \count($values) - 1), 
            $label, 
            $categories, 
            $values
        );
        $plot   = new PlotArea(null, [$series]);

        $legend = new Legend();
        $chart  = new Chart('chart name', new Title('Chart each years'), $legend, $plot);

        $chart->setTopLeftPosition('O3');
        $chart->setBottomRightPosition('Y20');

        return $chart;
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function startCell(): string
    {
        return 'A1';
    }

    public function headings(): array
    {
        $header = [];
        foreach ($this->listMonth as $key => $value) {
            $header[] = \DateTime::createFromFormat('!m', $value)->format('M');
        }

        return [
            [
                'ITEC Delivery Qty ' . $this->data[count($this->data) - 1]['year_ret'] . ' - ' . $this->data[0]['year_ret']
            ],
            [''],
            array_merge(['FY'], $header)
        ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $hasil = [];
        $keynya = 0;

        foreach ($this->data as $key => $value) {
            if ($key === 0 || $value['year_ret'] !== $this->data[$key - 1]['year_ret']) {
                $hasil[$keynya] = [
                    $value['year_ret']
                ];

                $keynya++;
            }
        }

        foreach ($hasil as $keyHasil => $valueHasil) {
            foreach ($this->listMonth as $keyMonth => $valueMonth) {

                $findYear = array_values(array_filter($this->data, function ($f) use ($valueMonth, $valueHasil) {
                    return $f['month_ret'] == $valueMonth && $f['year_ret'] == ($valueMonth == 1 || $valueMonth == 2 || $valueMonth == 3 ? ((int) $valueHasil[0] + 1) : $valueHasil[0]);
                }));

                if (count($findYear) > 0) {
                    array_push($hasil[$keyHasil], $findYear[0]['total']);
                } else {
                    if ($keyHasil === 0) {
                        // $hasil[$keyHasil][0] = $hasil[$keyHasil + 1][0] . ' FC';

                        $cekTotalForcast = FRCST_DLV_TYO::select(DB::raw('COALESCE(SUM(FDT_QTY), 0) AS FDT_QTY'))->where('FDT_MONTH', $valueMonth)->where('FDT_YEAR', ($valueMonth == 1 || $valueMonth == 2 || $valueMonth == 3 ? ((int) $hasil[$keyHasil + 1][0] + 1) : $hasil[$keyHasil + 1][0]))->first();

                        array_push($hasil[$keyHasil], $cekTotalForcast->FDT_QTY);

                        // if ($valueMonth === 3) {
                        //     $hasil[$keyHasil][0] = $hasil[$keyHasil + 1][0] . ' FC';
                        // }
                        
                        $hasil[$keyHasil][0] = $hasil[$keyHasil + 1][0] . ' FC';
                    } else {
                        array_push($hasil[$keyHasil], '0');
                    }
                }
            }
        }

        // logger($hasil);

        return collect($hasil);
    }

    public function toAlpha($num)
    {
        for ($r = ""; $num >= 0; $num = intval($num / 26) - 1)
            $r = chr($num % 26 + 0x41) . $r;
        return $r;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $highestRow = $event->sheet->getHighestRow();
                $highestColumn = $event->sheet->getHighestColumn();

                $chart = $this->charts();
                $event->sheet->getDelegate()->addChart($chart);

                $event->sheet->getDelegate()->getPageSetup()
                    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

                $event->sheet->getStyle('A1:A1')->applyFromArray([
                    'font' => [
                        'size' => '12',
                        'bold' => true
                    ]
                ]);

                $event->sheet->getDelegate()->mergeCells('A1:' . $highestColumn . '2');

                $event->sheet->getStyle('A1:' . $highestColumn . '3')->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle('A1:' . $highestColumn . '3')->getAlignment()->setVertical('center');

                
                $event->sheet->getStyle('A3:' . $highestColumn . '3')->getFill()->applyFromArray(['fillType' => 'solid','rotation' => 0, 'color' => ['rgb' => 'FFFF33'],]);
                $event->sheet->getStyle('A4:' . 'A' . $highestRow)->getAlignment()->setHorizontal('right');
                $event->sheet->getStyle('A4:' . 'A' . $highestRow)->getFill()->applyFromArray(['fillType' => 'solid','rotation' => 0, 'color' => ['rgb' => 'D9D9D9'],]);

                $event->sheet->getStyle('B4:' . $highestColumn . $highestRow)->getAlignment()->setHorizontal('right');
                $event->sheet->getStyle('B4:' . $highestColumn . $highestRow)->getNumberFormat()
                    ->setFormatCode(
                            \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                    );

                $event->sheet->getStyle('A1:' . $highestColumn . '2')->applyFromArray([
                    'font' => [
                        'size' => '12',
                        'bold' => true
                    ],
                ]);

                $event->sheet->styleCells(
                    'A3:' . $highestColumn . $highestRow,
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