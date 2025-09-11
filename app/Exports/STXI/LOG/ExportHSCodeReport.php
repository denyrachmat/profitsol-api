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

class ExportHSCodeReport implements FromCollection, WithHeadings, WithEvents
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
            'Item Desc',
            'QC Desc',
            'Maker PN',
            'Maker Name',
            'Series',
            'Maker Recomendation',
            'QC Doc',
            'Approval Date',
            'Approved By',
            'HS Code',
            'Section',
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

        // for ($i = 0; $i < (count($getBCData) + 12) - 1; $i++) {
        //     $cols2[] = '';
        // }

        // $cols2[] = 'HS CODE';

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
                'HS Code',
                'Compare Status',
                'Input By',
                'Input Date',
                'QC Approve Date'
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
        $cekDataOsOnly = array_values(array_filter($this->data, fn($f) => $f['cols'] == 'HSCD_APRVSTAT' && $f['param'] == '<>' && $f['value'] == '1'));
        $data = DB::connection('sqlsrv_log')->table(count($cekDataOsOnly) > 0 ? 'V_HSCODE_SYS' : 'V_HSCODE_SYS_DONE')
            ->orderBy('HSCD_BG', 'ASC')
            ->orderBy('HSCD_ITMCD', 'ASC')
            ->orderBy('HSCD_APPRVDT', 'DESC');
        // $data = DB::connection('sqlsrv_log')->table('V_HSCODE_SYS_DONE');

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

        // return $data->get();

        // $hasil = json_decode(json_encode($data->get()), true);
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
                            // else {
                            //     $listReg['TB-' . $valueHeader] = $listReg['TB-' . $valueHeader].';'.$valueReg->ZIRD_NMIJIN;
                            // }
                        }
                    }

                    // Tataniaga Post Border
                    foreach ($this->headerDet as $keyHeader => $valueHeader) {
                        if (in_array($valueHeader, $getParseJsonBeaList) && $valueReg->ZIRD_TYPE === 'import_regulation_post_border') {
                            $listReg['TPB-' . $valueHeader] = $valueReg->ZIRD_NMIJIN;
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
                'HSCD_ITMQCD' => $value['HSCD_ITMQCD'],
                'HSCD_MKCD' => $value['HSCD_MKCD'],
                'HSCD_MKCDQC' => $value['HSCD_MKCDQC'],
                'HSCD_SERIES' => $value['HSCD_SERIES'],
                'HSCD_MKRECCD' => $value['HSCD_MKRECCD'],
                'HSCD_QCDOC' => $value['HSCD_QCDOC'],
                // 'HSCD_STXICD' => $value['HSCD_STXICD'],
                'HSCD_SECT' => $value['HSCD_SECT'],
                'HSCD_TARIF' => $value['HSCD_TARIF'],
                'HSCD_PPN' => $value['HSCD_PPN'],
                'HSCD_PPH' => $value['HSCD_PPH'],
                'HSCD_PPNBM' => $value['HSCD_PPNBM'],
                'HSCD_CUKAI' => $value['HSCD_CUKAI'],
                'HSCD_UOM' => $value['HSCD_UOM'],
            ], $listReg, [
                'KUMHS' => '',
                'CATATAN_BAB' => '',
                'EXPLANATORY_NOTE' => '',
                'EXPORT_RESTRICTION' => '',
                'HS_CODE_WASTE' => '',
                'BM_WASTE' => '',
                'DESCRIPTION_WASTE' => '',
                'HISTORICAL' => '',
                'DG_CLASS' => '',
                'DG_FILE_NUMBER' => '',
                'DG_REGULATION' => (clone $checkReg)->count() > 0 ? $checkReg->pluck('ZIRD_SKEPNO')->implode(', ') : '',
                // 'DG_REGULATION' => '',
                'REMARK_1' => '',
                'HSCD_STXICD' => $value['HSCD_STXICD'],
                'MEGA_HSCODE' => $value['MEGA_HSCODE'],
                'QC_HSCODE' => $value['QC_HSCODE'],
                'COMPARE_STAT' => $value['HSCD_DIFFERENCE'],
                'HSCD_APPRVDT' => $value['HSCD_APPRVDT'],
                'HSCD_LASTAPPRV' => $value['HSCD_LASTAPPRV'],
                'INPUT_USERS' => $value['INPUT_USERS'],
                'INPUT_DATE' => $value['INPUT_DATE'],
                'QC_APRVDT' => $value['QC_APRVDT']
            ]);
        }

        // logger($listReg);
        logger($hasil);

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

                $cekDataOsOnly = array_values(array_filter($this->data, fn($f) => $f['cols'] == 'HSCD_APRVSTAT' && $f['param'] == '<>' && $f['value'] == '1'));

                // if (count($cekDataOsOnly) === 0) {
                // }


                $event->sheet->getStyle("A1:{$highestColumn}3")->applyFromArray([
                    'font' => [
                        'size' => '11',
                        'bold' => true
                    ]
                ]);
                for ($i = 0; $i < 20; $i++) {
                    $event->sheet->getDelegate()->mergeCells("{$this->toAlpha($i)}1:{$this->toAlpha($i)}3");
                }

                $event->sheet->getDelegate()->mergeCells("U1:AA1");
                $event->sheet->getDelegate()->mergeCells("U2:V2");
                $event->sheet->getDelegate()->mergeCells("X2:Y2");
                $event->sheet->getDelegate()->mergeCells("Z2:AA2");
                $event->sheet->getDelegate()->mergeCells("AB2:AC2");
                $event->sheet->getDelegate()->mergeCells("AE2:AF2");
                $event->sheet->getDelegate()->mergeCells("AG2:AH2");
                $event->sheet->getDelegate()->mergeCells("AB1:AH1");
                $event->sheet->getDelegate()->mergeCells("AV1:AV3");
                $event->sheet->getDelegate()->mergeCells("AW1:AW3");
                $event->sheet->getDelegate()->mergeCells("AX1:AX3");

                for ($i = 34; $i < 47; $i++) {
                    $event->sheet->getDelegate()->mergeCells("{$this->toAlpha($i)}1:{$this->toAlpha($i)}3");
                }

                $event->sheet->getStyle("A1:{$highestColumn}3")->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle("A1:{$highestColumn}3")->getAlignment()->setVertical('center');

                // Wrap text in column A
                $event->sheet->getStyle("U4:AH{$highestRow}")->applyFromArray([
                    'alignment' => [
                        'wrapText' => true,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
                    ]
                ]);

                for ($row = 4; $row <= $highestRow; $row++) {
                    $cellValue = $event->sheet->getCell("AU{$row}")->getValue();
                    // logger($cellValue);
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
