<?php

namespace App\Exports\STXI\TOS;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ExportQuestionAnalytics implements FromCollection, WithHeadings, WithEvents
{
    use Exportable, RegistersEventListeners;
    private $data, $title;

    public function __construct($data, $title)
    {
        $this->data = $data;
        $this->title = $title;
    }

    public function headings(): array
    {
        return [
            [
                'Training Title: ', $this->title['cfmt_title'],
            ],
            [
                'Period: ', date('d M Y', strtotime($this->title['cfsd_start_quiz'])). ' - ' .date('d M Y', strtotime($this->title['cfsd_end_quiz']))
            ],
            [],
            [
                'Question',
                'Answers',
                'Total Users Wrong Answers',
                'Total Users Right Answers'
            ],
            []
        ];
    }

    public function collection()
    {
        $hasil = [];
        foreach ($this->data as $key => $value) {
            $hasil[] = [
                'question' => strip_tags($value['content']['label']),
                'answers' => strip_tags($value['answers']),
                'fail' => count($value['failData']) === 0 ? '0' : (int)count($value['failData']),
                'success' => (int)count($value['successData'])
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

                $event->sheet->getStyle('A1:B2')->applyFromArray([
                    'font' => [
                        'size' => '12',
                        'bold' => true
                    ]
                ]);

                $event->sheet->getStyle('A4:H4')->applyFromArray([
                    'font' => [
                        'size' => '12',
                        'bold' => true
                    ]
                ]);

                $event->sheet->getStyle('A4:H4')->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle('A4:H4')->getAlignment()->setVertical('center');

                $event->sheet->getStyle('A5:A'.$highestRow)->getAlignment()->setWrapText(true);
                $event->sheet->getStyle('A5:A'.$highestRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                
                $event->sheet->getStyle('B5:B'.$highestRow)->getAlignment()->setWrapText(true);
                $event->sheet->getStyle('B5:B'.$highestRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

                // $event->sheet->getStyle('D5:E' . $highestRow)->getNumberFormat()
                // ->setFormatCode(
                //         NumberFormat::FORMAT_DATE_DATETIME
                // );

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
            }
        ];
    }
}
