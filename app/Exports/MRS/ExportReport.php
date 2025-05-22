<?php

namespace App\Exports\MRS;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use App\Models\MRS\MRSReportColsDet;
use App\Models\MRS\MRSReportMstr;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ExportReport implements FromCollection, WithHeadings, WithEvents
{
    use RegistersEventListeners;

    private $data, $id;

    public function __construct($data, $id)
    {
        $this->data = $data;
        $this->id = $id;
    }

    public function headings(): array
    {
        $getHeader = MRSReportMstr::where('id', $this->id)->first();
        $getData = MRSReportColsDet::where('mrm_id', $this->id)
            ->where('mrcd_isActive', 1)
            ->where('mrcd_isExported', 1)
            ->where('mrcd_col_prop', 'cols')
            ->get();

        $hasil = [
            [
                $getHeader->mrm_name
            ],
            [
                'Report Created At :',
                date('Y-m-d H:i:s')
            ],
            [''],
            []
        ];
        foreach ($getData as $key => $value) {
            if ($value->mrcd_isExported) {
                $hasil[3][] = $value->mrcd_label;
            }
        }

        return $hasil;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $hasil = [];
        $getDataHeaderFirst = MRSReportColsDet::where('mrm_id', $this->id)
            ->where('mrcd_isActive', 1)
            ->where('mrcd_isExported', 1)
            ->where('mrcd_col_prop', 'cols');

        $getDataHeaderChecker = (clone $getDataHeaderFirst)->pluck('mrcd_field')->toArray();
        $getDataHeader = (clone $getDataHeaderFirst)
            ->get()
            ->toArray();

        if (is_object($this->data)) {
            $getData = json_decode(json_encode($this->data), true);
        } else {
            $getData = $this->data;
        }

        foreach ($getData as $key => $value) {
            $cekCols = array_filter(array_keys((array)$value), function ($f) use ($getDataHeaderChecker) {
                return in_array($f, $getDataHeaderChecker);
            });

            foreach ($cekCols as $keyCols => $valueCols) {
                $cekTest = array_values(array_filter($getDataHeader, function($fc) use ($valueCols){
                    return $fc['mrcd_field'] === $valueCols;
                }));

                $hasil[$key][$valueCols] = count($cekTest) > 0
                    ? (
                        $cekTest[0]['mrcd_fieldType'] === 'date' || $cekTest[0]['mrcd_fieldType'] === 'datetime'
                        ? Date::PHPToExcel(date('Y-m-d', strtotime($value->$valueCols)))
                        : $value->$valueCols
                    )
                    : $value->$valueCols;
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

                $event->sheet->getStyle('A4:' . $highestColumn . '4')->applyFromArray([
                    'font' => [
                        'size' => '12',
                        'bold' => true
                    ]
                ]);

                $event->sheet->getStyle('A1:B2')->applyFromArray([
                    'font' => [
                        'size' => '18',
                        'bold' => true
                    ]
                ]);

                // $event->sheet->getStyle('C')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_XLSX15);
                // $event->sheet->getStyle('D')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_XLSX15);

                $event->sheet->getStyle('A4:' . $highestColumn . '4')->getAlignment()->setHorizontal('center');
                // $event->sheet->getStyle('I3:I' . $highestRow)->getNumberFormat()
                // ->setFormatCode(
                //         \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                // );

                // $event->sheet->getStyle('G3:G' . $highestRow)->getNumberFormat()
                // ->setFormatCode(
                //         \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                // );

                // $event->sheet->getStyle('H3:H' . $highestRow)->getNumberFormat()
                // ->setFormatCode(
                //         \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                // );

                $event->sheet->styleCells(
                    'A4:' . $highestColumn . $highestRow,
                    [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            ],
                        ]
                    ]
                );

                $getDataHeader = MRSReportColsDet::where('mrm_id', $this->id)
                    ->where('mrcd_isActive', 1)
                    ->where('mrcd_isExported', 1)
                    ->where('mrcd_col_prop', 'cols')
                    ->get();

                $start = 0;
                foreach ($getDataHeader as $key => $value) {
                    if ($value->mrcd_fieldType === 'date' || $value->mrcd_fieldType === 'datetime') {
                        $event->sheet->getStyle($this->toAlpha($start))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_XLSX15);
                    } elseif ($value->mrcd_fieldType === 'int' || $value->mrcd_fieldType === 'float') {
                        $event->sheet->getStyle($this->toAlpha($start))->getNumberFormat()->setFormatCode(
                            NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        );
                    }

                    $start++;
                }

                // foreach(range('A', $highestColumn) as $columnID) {
                // $event->sheet->getColumnDimension($columnID)->setAutoSize(true) ;
                // }

                // $event->sheet->getDelegate()->mergeCells('A1:'.$highestColumn.'1');

                // $event->sheet->getStyle('G5:'.$highestColumn.$highestRow)->getAlignment()->setHorizontal('right');
            }
        ];
    }

    public function toAlpha($num)
    {
        for ($r = ""; $num >= 0; $num = intval($num / 26) - 1)
            $r = chr($num % 26 + 0x41) . $r;
        return $r;
    }
}
