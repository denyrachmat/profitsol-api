<?php

namespace App\Exports\STXI\LOG;

use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\Exportable;
use App\Models\STXI\LOG\HSCodeGroupBeaDetail;
use App\Models\STXI\LOG\INSWDataDocBeaMaster;
use App\Models\STXI\LOG\HSCodeUplMaster;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;

class ExportHSCodeReport implements FromCollection, WithHeadings,WithEvents
{
    use RegistersEventListeners, Exportable;
    public $data;
    function __construct($data = [])
    {
        $this->data = $data;
    }
    public function startRow(): int
    {
        return 2;
    }

    public function headings(): array
    {
        $firstPart = [
            'Part Code',
            'BG',
            'Biz Unit',
            'Item Desc',
            'QC Desc',
            'Maker PN',
            'Maker Name',
            'Series',
            'Maker Recomendation',
            'QC Doc',
            'Approval Date',
            'HS Code',
            'Section',
            'Tarif (%)',
            'PPN (%)',
            'PPH (%)',
            'PPnBM (%)',
            'Cukai (%)',
            'UoM'
        ];

        $getBCData = INSWDataDocBeaMaster::whereNotIn('ZIDBD_DOCCD', [611, 632])->get();
        $cols1 = [
            'TATANIAGA BORDER'
        ];

        $cols2 = [
            'TATANIAGA POST BORDER'
        ];

        for ($i = 0; $i < count($getBCData) - 1; $i++) {
            $cols1[] = '';
            $cols2[] = '';
        }

        $firstPartEmpty = [];
        for ($j = 0; $j < count($firstPart); $j++) {
            $firstPartEmpty[] = '';
        }

        $hasil[] = array_merge(
            $firstPart,
            $cols1,
            $cols2,
            [
                'KUMHS',
                'Catatan BAB',
                'Explanatory Note',
                'Export Restriction',
                'HS Code Waste',
                'BM Waste',
                'Description Waste',
                'HISTORICAL',
                'DG Class',
                'DG File Number',
                'DG Regulation',
                'Remark-1'
            ]
        );

        $listPLB = ['16', '28'];
        $listGenImport = ['20'];
        $listTPB = ['23', '25'];
        $listFTZ = ['511', '513'];

        $colsDet1 = [];
        for ($i = 0; $i < count($listPLB); $i++) {
            $colsDet1[] = $i === 0 ? 'PLB' : '';
        }

        $colsDet2 = [];
        for ($i = 0; $i < count($listGenImport); $i++) {
            $colsDet2[] = $i === 0 ? 'General Import' : '';
        }

        $colsDet3 = [];
        for ($i = 0; $i < count($listTPB); $i++) {
            $colsDet3[] = $i === 0 ? 'TPB' : '';
        }

        $colsDet4 = [];
        for ($i = 0; $i < count($listFTZ); $i++) {
            $colsDet4[] = $i === 0 ? 'FTZ' : '';
        }

        $colsComb = array_merge($colsDet1, $colsDet2, $colsDet3, $colsDet4);

        $hasil[] = array_merge(
            $firstPartEmpty,
            $colsComb,
            $colsComb
        );

        $hasil[] = array_merge(
            $firstPartEmpty,
            (clone $getBCData)->pluck('ZIDBD_DOCNM')->toArray(),
            (clone $getBCData)->pluck('ZIDBD_DOCNM')->toArray(),
        );

        return $hasil;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $data = DB::connection('sqlsrv_log')->table('V_HSCODE_SYS');

        if (count($this->data) > 0) {
            foreach ($this->data as $key => $valueCols) {
                $data->where($valueCols['cols'], $valueCols['param'], $valueCols['param'] === 'like' ? "%{$valueCols['value']}%" : $valueCols['value']);
            }
        }

        $hasil = [];
        foreach ($data->get() as $key => $value) {
            $hasil[] = $value;
        }

        return collect($hasil);
    }

    public function headerGroupRecurs($initData, $initHeader, $submitedData = [])
    {
        if (count($submitedData) === 0) {
            $submitedData[] = $initHeader;
        }

        $nowData = current($initData);
        if (count($nowData['child_group']) > 0) {
            $submitedData[count($submitedData) - 1] = array_merge();
        }
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

                $event->sheet->getDelegate()->getPageSetup()
                    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

                $event->sheet->getStyle("A1:{$highestColumn}3")->applyFromArray([
                    'font' => [
                        'size' => '11',
                        'bold' => true
                    ]
                ]);

                for ($i=0; $i < 19; $i++) {
                    $event->sheet->getDelegate()->mergeCells("{$this->toAlpha($i)}1:{$this->toAlpha($i)}3");
                }

                $event->sheet->getDelegate()->mergeCells("T1:Z1");
                $event->sheet->getDelegate()->mergeCells("T2:U2");
                $event->sheet->getDelegate()->mergeCells("W2:X2");
                $event->sheet->getDelegate()->mergeCells("Y2:Z2");
                $event->sheet->getDelegate()->mergeCells("AA2:AB2");
                $event->sheet->getDelegate()->mergeCells("AD2:AE2");
                $event->sheet->getDelegate()->mergeCells("AF2:AG2");
                $event->sheet->getDelegate()->mergeCells("AA1:AG1");

                for ($i=33; $i < 45; $i++) {
                    $event->sheet->getDelegate()->mergeCells("{$this->toAlpha($i)}1:{$this->toAlpha($i)}3");
                }

                $event->sheet->getStyle("A1:{$highestColumn}3")->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle("A1:{$highestColumn}3")->getAlignment()->setVertical('center');

                $event->sheet->styleCells(
                    'A1:'.$highestColumn.$highestRow,
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
