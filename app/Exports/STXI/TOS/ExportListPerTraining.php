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

class ExportListPerTraining implements FromCollection, WithHeadings, WithEvents
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
                'Username',
                'Full Name',
                'First Time Answers',
                'Last Time Answers',
                'Learn Times',
                'Grade',
                'Status',
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
                'email' => $value['email'],
                'fullname' => $value['fullname'],
                'first_time_answer' => empty($value['first_time_answer']) ? null : Date::PHPToExcel(date('Y-m-d h:i:s', strtotime($value['first_time_answer']))),
                'last_time_answer' => empty($value['last_time_answer']) ? null : Date::PHPToExcel(date('Y-m-d h:i:s', strtotime($value['last_time_answer']))),
                'learn_time' => $value['learn_time'],
                'grade' => $value['grade'],
                'status' => $value['status'],
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

                $event->sheet->getStyle('A4:G4')->applyFromArray([
                    'font' => [
                        'size' => '12',
                        'bold' => true
                    ]
                ]);

                $event->sheet->getStyle('A4:G4')->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle('A4:G4')->getAlignment()->setVertical('center');

                $event->sheet->getStyle('C5:D' . $highestRow)->getNumberFormat()
                ->setFormatCode(
                        NumberFormat::FORMAT_DATE_DATETIME
                );

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
