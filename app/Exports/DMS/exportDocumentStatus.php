<?php

namespace App\Exports\DMS;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class exportDocumentStatus implements FromCollection, WithHeadings, WithStartRow, WithEvents
{
    use Exportable;
    use RegistersEventListeners;

    private $data;
    /**
    * @return \Illuminate\Support\Collection
    */
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $hasil = [];

        foreach ($this->data as $key => $value) {
            $hasil[] = [
                'no' => $key + 1,
                'doc_name' => $value['doc']['doc_real_name'],
                'uploaded_time' => date('d M Y H:i:s', strtotime($value['doc']['created_at'])),
                'sent_time' => date('d M Y H:i:s', strtotime($value['created_at'])),
                'author' => $value['doc']['users']['first_name'],
                'last_apprv' => $value['lastapprv']['users']['first_name'].' '.$value['lastapprv']['users']['last_name'],
                'last_apprv_stat' => $value['lastapprv']['apprv_hist_status'] == '1'
                    ? (
                        $value['lastapprv']['approver_level'] === $value['getallapprover'][count($value['getallapprover']) -1]['apprv_level']
                        ? "Full Approved"
                        : "Partially Approved"
                      )
                    : "Rejected",
            ];
        }
        return collect($hasil);
    }

    public function headings(): array
    {
        return [
            ['DMS DOCUMENT STATUS'],
            ['PT SUMITRONICS INDONESIA'],
            [],
            [
                'NO',
                'DOCUMENT NAME',
                'UPLOADED DATETIME',
                'APPROVAL SENT DATETIME',
                'AUTHOR',
                'LAST APPROVER',
                'LAST APPROVAL STATUS'
            ]
         ];
    }

    public function startRow() : int
    {
        return 5;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A1:A2')->applyFromArray([
                    'font' => [
                        'size' => '15',
                        'bold' => true
                    ]
                ]);

                $event->sheet->getStyle('A4:G4')->applyFromArray([
                    'font' => [
                        'bold' => true
                    ]
                ]);

                $highestRow = $event->sheet->getHighestRow();
                $highestColumn = $event->sheet->getHighestColumn();
                
                $event->sheet->styleCells(
                    'A4:G4',
                    [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            ],
                        ]
                    ]
                );

                $event->sheet->styleCells(
                    'A5:'.$highestColumn.$highestRow,
                    [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            ],
                        ]
                    ]
                );

                $event->sheet->getStyle('A1:H4')->getAlignment()->setHorizontal('center');

                $event->sheet->getDelegate()->mergeCells('A1:G1');
                $event->sheet->getDelegate()->mergeCells('A2:G2');
            },
        ];
    }
}
