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

use App\Models\STXI\LOG\INSWDataRegDet;

class ExportHSCodeReportNew implements FromCollection, WithHeadings, WithEvents
{
    use RegistersEventListeners, Exportable;
    public $data, $withHist;
    function __construct($data = [], $withHist = false)
    {
        $this->data = $data;
        $this->withHist = $withHist;
        $this->headerDet = [];
    }
    public function startRow(): int
    {
        return 4;
    }

    public function headings(): array
    {
        $firstPart = [
            'Part Code',
            'BG',
            'Biz Unit',
            'Description 1',
            'Description 2',
            'Description 3',
            'QC Desc',
            'Maker PN',
            'Maker Name',
            'Series',
            'Maker Recomendation',
            'QC Doc',
            'Section',
            'HS Code',
            'MEGA',
            'QC',
            'Tarif (%)',
            'PPN (%)',
            'PPH (%)',
            'PPnBM (%)',
            'Cukai (%)',
            'UoM'
        ];

        $cekDataOsOnly = array_values(array_filter($this->data, fn($f) => $f['cols'] == 'HSCD_APRVSTAT' && $f['param'] == '<>' && $f['value'] == '1'));

        if (count($cekDataOsOnly) > 0) {
            // return $firstPart;
        }

        $getBCData = INSWDataDocBeaMaster::whereNotIn('ZIDBD_DOCCD', [611, 632])->get();
        $cols1 = [
            'TATANIAGA BORDER'
        ];

        $cols2 = [
            'TATANIAGA POST BORDER'
        ];
        for ($i = 0; $i < (count($getBCData)) - 1; $i++) {
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
                'Regulation',
                'Remark-1',
                'Compare Status',
                'Input By',
                'Input Date',
                'Approved By',
                'Approved Date',
                'QC Approve Date',
                'GROSS WG',
                'NET WG',
                'SUP CD',
                'SUP NM'
            ]
        );

        $listGenImport = ['20'];
        $listPLB = ['16', '28'];
        $listTPB = ['25', '23'];
        $listFTZ = ['511', '513'];

        $this->headerDet = array_merge($listGenImport, $listPLB, $listTPB, $listFTZ);

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

        $row3 = array_fill(0, 58, '');
        $bc = (clone $getBCData)->pluck('ZIDBD_DOCNM')->toArray();
        $row3[13] = 'WEB';
        $row3[14] = 'MEGA';
        $row3[15] = 'QC';
        for ($i = 0; $i < count($bc); $i++) {
            $row3[22 + $i] = $bc[$i];
            $row3[29 + $i] = $bc[$i];
        }

        $hasil[] = $row3;

        return $hasil;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $cekDataOsOnly = array_values(array_filter($this->data, fn($f) => $f['cols'] == 'HSCD_APRVSTAT' && $f['param'] == '<>' && $f['value'] == '1'));
        $data = DB::connection('sqlsrv_log')->table(count($cekDataOsOnly) > 0 ? 'V_HSCODE_SYS' : 'V_HSCODE_SYS_DONE')
            ->orderBy('HSCD_BG', 'ASC')
            ->orderBy('HSCD_ITMCD', 'ASC')
            ->orderBy('HSCD_APPRVDT', 'DESC');

        if (
            count($this->data) > 0 && count(array_filter($this->data, function ($f) {
                return !empty($f['value']);
            })) > 0
        ) {
            foreach ($this->data as $key => $value) {
                if ($value['cols'] !== 'HSCD_APRVSTAT') {
                    $data->where($value['cols'], $value['param'], $value['param'] === 'like' ? "%{$value['value']}%" : $value['value']);
                }
            }
        }

        if (!$this->withHist) {
            $data->where('IS_DELETED', 0);
        }

        $datanya = json_decode(json_encode($data->get()), true);
        $hasil = [];
        foreach ($datanya as $key => $value) {
            $checkReg = INSWDataRegDet::select([
                DB::raw('CAST(ZID_HSCODE as varchar(200)) as ZID_HSCODE'),
                DB::raw('CAST(ZIRD_TYPE AS NVARCHAR(255)) as ZIRD_TYPE'),
                DB::raw('CAST(ZIRD_KDIJIN AS NVARCHAR(255)) as ZIRD_KDIJIN'),
                DB::raw('CAST(ZIRD_NMIJIN AS NVARCHAR(MAX)) as ZIRD_NMIJIN'),
                DB::raw('CAST(ZIRD_BEALIST AS NVARCHAR(MAX)) as ZIRD_BEALIST'),
                DB::raw('CAST(ZIRD_MODUL AS NVARCHAR(255)) as ZIRD_MODUL'),
                DB::raw('CAST(ZIRD_SKEPNO AS NVARCHAR(255)) as ZIRD_SKEPNO')
            ])
                ->where(DB::raw('CAST(ZID_HSCODE as varchar(200))'), $value['HSCD_STXICD'])
                ->whereNull('deleted_at')
                ->groupBy([
                    DB::raw('CAST(ZID_HSCODE as varchar(200))'),
                    DB::raw('CAST(ZIRD_TYPE AS NVARCHAR(255))'),
                    DB::raw('CAST(ZIRD_KDIJIN AS NVARCHAR(255))'),
                    DB::raw('CAST(ZIRD_NMIJIN AS NVARCHAR(MAX))'),
                    DB::raw('CAST(ZIRD_BEALIST AS NVARCHAR(MAX))'),
                    DB::raw('CAST(ZIRD_MODUL AS NVARCHAR(255))'),
                    DB::raw('CAST(ZIRD_SKEPNO AS NVARCHAR(255))')
                ]);

            $listReg = [];
            if ((clone $checkReg)->count() > 0) {
                foreach ($checkReg->get() as $key => $valueReg) {
                    $getParseJsonBeaList = [];
                    if (!empty($valueReg->ZIRD_BEALIST)) {
                        $getParseJsonBeaList = json_decode($valueReg->ZIRD_BEALIST);
                    }

                    // Tataniaga Border
                    foreach ($this->headerDet as $keyHeader => $valueHeader) {
                        if (count($getParseJsonBeaList) > 0 && in_array($valueHeader, $getParseJsonBeaList) && ($valueReg->ZIRD_TYPE === 'import_regulation' || $valueReg->ZIRD_TYPE === 'import_regulation_border')) {
                            $listReg['TB-' . $valueHeader] = $valueReg->ZIRD_NMIJIN;
                        } else {
                            if (empty($valueReg->ZIRD_NMIJIN) || !isset($listReg['TB-' . $valueHeader]) || empty($listReg['TB-' . $valueHeader]) || $listReg['TB-' . $valueHeader] === '-') {
                                $listReg['TB-' . $valueHeader] = '-';
                            }
                        }
                    }

                    // Tataniaga Post Border
                    foreach ($this->headerDet as $keyHeader => $valueHeader) {
                        if (in_array($valueHeader, $getParseJsonBeaList) && $valueReg->ZIRD_TYPE === 'import_regulation_post_border') {
                            if (isset($listReg['TPB-' . $valueHeader]) && !empty($listReg['TPB-' . $valueHeader]) && $listReg['TPB-' . $valueHeader] !== '-') {
                                $listReg['TPB-' . $valueHeader] .= "\n -" . $valueReg->ZIRD_NMIJIN;
                            } else {
                                $listReg['TPB-' . $valueHeader] = '- '. $valueReg->ZIRD_NMIJIN;
                            }
                        } else {
                            if (!isset($listReg['TPB-' . $valueHeader]) && empty($listReg['TPB-' . $valueHeader])) {
                                $listReg['TPB-' . $valueHeader] = '-';
                            }
                        }
                    }
                }

                // Check for export restriction
                $checkRegExport = (clone $checkReg)->where('ZIRD_TYPE', 'export_regulation')->first();
                if (empty($checkRegExport)) {
                    for ($i = 0; $i < 3; $i++) {
                        $listReg['TEK-' . $i] = '';
                    }

                    $listReg['TES-' . $key] = '-';
                } else {
                    for ($i = 0; $i < 3; $i++) {
                        $listReg['TEK-' . $i] = '';
                    }

                    $listReg['TES-' . $checkRegExport->ZIRD_MODUL] = $checkRegExport->ZIRD_MODUL;
                }
            } else {
                // Tataniaga Border
                foreach ($this->headerDet as $keyHeader => $valueHeader) {
                    $listReg['TB-' . $valueHeader] = '-';
                }

                // Tataniaga Post Border
                foreach ($this->headerDet as $keyHeader => $valueHeader) {
                    $listReg['TPB' . $valueHeader] = '-';
                }

                // Export Restriction
                for ($i = 0; $i < 3; $i++) {
                    $listReg['TEK-' . $i] = '';
                }

                $listReg['TES'] = '-';
            }

            $itemnya = $value['HSCD_ITMCD'];
            if (count($cekDataOsOnly) > 0) {
                if ($key > 0) {
                    if ($value['HSCD_ITMCD'] === $datanya[$key - 1]['HSCD_ITMCD']) {
                        $itemnya = '';
                    }
                }
            }

            $hasil[] = array_merge([
                'HSCD_ITMCD' => $itemnya,
                'HSCD_BG' => $value['HSCD_BG'],
                'HSCD_BIZ' => $value['HSCD_BIZ'],
                'HSCD_ITMD' => $value['HSCD_ITMD'],
                'DESC_2' => $value['HSCD_ITMQCD'],
                'DESC_3' => '',
                'HSCD_ITMQCD' => $value['HSCD_ITMQCD'],
                'HSCD_MKCD' => $value['HSCD_MKCD'],
                'HSCD_MKCDQC' => $value['HSCD_MKCDQC'],
                'HSCD_SERIES' => $value['HSCD_SERIES'],
                'HSCD_MKRECCD' => $value['HSCD_MKRECCD'],
                'HSCD_QCDOC' => $value['HSCD_QCDOC'],
                'HSCD_SECT' => $value['HSCD_SECT'],
                'HSCD_STXICD' => $value['HSCD_STXICD'],
                'MEGA_HSCODE' => $value['MEGA_HSCODE'],
                'QC_HSCODE' => $value['QC_HSCODE'],
                'HSCD_TARIF' => $value['HSCD_TARIF'],
                'HSCD_PPN' => $value['HSCD_PPN'],
                'HSCD_PPH' => $value['HSCD_PPH'],
                'HSCD_PPNBM' => $value['HSCD_PPNBM'],
                'HSCD_CUKAI' => $value['HSCD_CUKAI'],
                'HSCD_UOM' => $value['HSCD_UOM'],
            ], $listReg, [
                'HS_CODE_WASTE' => '',
                'BM_WASTE' => '',
                'DESCRIPTION_WASTE' => '',
                'HISTORICAL' => '',
                'DG_CLASS' => '',
                'DG_FILE_NUMBER' => '',
                'DG_REGULATION' => (clone $checkReg)->count() > 0
                    ? collect($checkReg->pluck('ZIRD_SKEPNO')->toArray())
                        ->unique()
                        ->filter(fn($v) => !empty($v) && $v !== '-')
                        ->implode(', ')
                    : '',
                'REMARK_1' => '',
                'COMPARE_STAT' => $value['HSCD_DIFFERENCE'],
                'INPUT_USERS' => $value['INPUT_USERS'],
                'INPUT_DATE' => $value['INPUT_DATE'] ?? '-',
                'HSCD_LASTAPPRV' => $value['HSCD_LASTAPPRV'],
                'HSCD_APPRVDT' => $value['HSCD_APPRVDT'],
                'QC_APRVDT' => $value['QC_APRVDT'],
                'GROSS_WG' => $value['HSCD_ITMGW'],
                'NET_WG' => $value['HSCD_ITMNW'],
                'SUP_CD' => $value['HSCD_SUPCD'],
                'SUP_NM' => $value['HSCD_SUPNM']
            ]);
        }

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

                $event->sheet->getDelegate()->getPageSetup()
                    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

                $event->sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
                    'font' => [
                        'size' => '11',
                        'bold' => true
                    ]
                ]);

                $event->sheet->styleCells(
                    'A1:' . $highestColumn . $highestRow,
                    [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            ],
                        ]
                    ]
                );

                $event->sheet->getStyle("A1:{$highestColumn}3")->applyFromArray([
                    'font' => [
                        'size' => '11',
                        'bold' => true
                    ]
                ]);

                // Main columns A-V (index 0-21), merge 1:3
                // HS Code / MEGA / QC (index 13,14,15) merged 1:2 to surface row3 WEB/MEGA/QC
                $oneTwo = [13, 14, 15];
                for ($i = 0; $i < 22; $i++) {
                    $merge = in_array($i, $oneTwo)
                        ? "{$this->toAlpha($i)}1:{$this->toAlpha($i)}2"
                        : "{$this->toAlpha($i)}1:{$this->toAlpha($i)}3";
                    $event->sheet->getDelegate()->mergeCells($merge);
                }

                // Tataniaga Border
                $event->sheet->getDelegate()->mergeCells("W1:AC1");
                // PLB
                $event->sheet->getDelegate()->mergeCells("W2:X2");
                // TPB
                $event->sheet->getDelegate()->mergeCells("Z2:AA2");
                // FTZ
                $event->sheet->getDelegate()->mergeCells("AB2:AC2");

                // Tataniaga Post Border
                $event->sheet->getDelegate()->mergeCells("AD1:AJ1");
                // PLB
                $event->sheet->getDelegate()->mergeCells("AD2:AE2");
                // TPB
                $event->sheet->getDelegate()->mergeCells("AG2:AH2");
                // FTZ
                $event->sheet->getDelegate()->mergeCells("AI2:AJ2");

                // HS Code
                $event->sheet->getDelegate()->mergeCells("N1:P2");

                // Detail columns AK-BF (index 36-57), merge 1:3
                for ($i = 36; $i < 58; $i++) {
                    $event->sheet->getDelegate()->mergeCells("{$this->toAlpha($i)}1:{$this->toAlpha($i)}3");
                }

                $event->sheet->getStyle("A1:{$highestColumn}3")->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle("A1:{$highestColumn}3")->getAlignment()->setVertical('center');

                // Wrap text in border + post border + early detail
                $event->sheet->getStyle("W4:AN{$highestRow}")->applyFromArray([
                    'alignment' => [
                        'wrapText' => true,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
                    ]
                ]);

                // Text format: A (Part Code), H (Maker PN), J (Series)
                foreach (['A', 'H', 'J'] as $col) {
                    $event->sheet->getDelegate()
                        ->getStyle("{$col}4:{$col}{$highestRow}")
                        ->getNumberFormat()
                        ->setFormatCode('@');
                }

                foreach (range('A', $highestColumn) as $col) {
                    $event->sheet->getDelegate()->getColumnDimension($col)->setAutoSize(true);
                }

                for ($row = 4; $row <= $highestRow; $row++) {
                    $cellValue = $event->sheet->getCell("AW{$row}")->getValue();
                    if (strtoupper(trim($cellValue)) !== 'CONSISTENT') {
                        $event->sheet->getStyle("A{$row}:{$highestColumn}{$row}")->applyFromArray([
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => [
                                    'rgb' => 'FFFF00'
                                ]
                            ]
                        ]);
                    }
                }
            }
        ];
    }
}
