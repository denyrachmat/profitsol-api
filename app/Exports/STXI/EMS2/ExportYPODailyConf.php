<?php

namespace App\Exports\STXI\EMS2;

use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ExportYPODailyConf implements FromCollection, WithHeadings, WithEvents
{
    use RegistersEventListeners, Exportable;
    public $fdate, $ldate;
    function __construct($fdate, $ldate)
    {
        $this->fdate = $fdate;
        $this->ldate = $ldate;
    }

    function getArrPeriod(): CarbonPeriod
    {
        $period = CarbonPeriod::create($this->fdate, $this->ldate)->filter('isWeekday');

        return $period;
    }

    public function headings(): array
    {
        $headDateSect = [];
        $headDateSectDet = [];
        foreach ($this->getArrPeriod() as $key => $valuePeriod) {
            if ($key > 0) {
                $headDateSect = array_merge($headDateSect, [
                    'Time Line Supplier (Target ETA YEID)',
                    'ETD Supplier (am/pm)',
                    $valuePeriod->format('d/M/Y'),
                    '',
                    ''
                ]);

                $headDateSectDet = array_merge($headDateSectDet, [
                    '',
                    '',
                    'DELIVERY QTY',
                    'Stock at Sumitronics (Pcs)',
                    'Balance Stock (pcs)',
                ]);
            } else {
                $headDateSect = [
                    $valuePeriod->format('d/M/Y'),
                    '',
                    ''
                ];

                $headDateSectDet = array_merge($headDateSectDet, [
                    'DELIVERY QTY',
                    'Stock at Sumitronics (Pcs)',
                    'Balance Stock (pcs)',
                ]);
            }
        }

        // logger($headDateSect);

        return [
            [
                'Confirmation Daily PO & Stock F/G N+1  : ' . date('d M Y', strtotime($this->fdate)) . ')',
            ],
            [
                'Nama Supplier : (PT SUMITRONICS INDONESIA)',
            ],
            array_merge(
                [
                    'No',
                    'Part No',
                    'Part Name'
                ],
                $headDateSect
            ),
            array_merge(
                [
                    '',
                    '',
                    ''
                ],
                $headDateSectDet
            )
        ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $hasil = [];

        foreach ($this->getArrPeriod() as $key => $valuePeriod) {
            if ($key === 0) {
                foreach ($this->getData($valuePeriod->format('Y-m-d')) as $keyData => $valData) {
                    $hasil[] = [
                        'no' => $keyData + 1,
                        'part' => $valData->KSHP_ITMCD,
                        'name' => $valData->MITM_SPTNO,
                        'dlv'. $key => $valData->KSHP_DELQT,
                        'stock'. $key => $valData->OPN_QT,
                        'tot'. $key => $valData->OPN_QT - $valData->KSHP_DELQT,
                        '1'. $key => '11.00  - 13.00  AM',
                        '2'. $key => 'AM'
                    ];
                }
            } else {
                // First Date Based data
                $listItem = [];
                foreach ($hasil as $keyFirst => $valueFirst) {
                    $listItem[] = $valueFirst['part'];
                    $cekItem = $this->getData($valuePeriod->format('Y-m-d'), $valueFirst['part']);

                    logger($cekItem);

                    if (count($cekItem) > 0) {
                        foreach ($this->getData($valuePeriod->format('Y-m-d'), $valueFirst['part']) as $keyData => $valData) {
                            $hasil[$keyFirst] = array_merge(
                                $hasil[$keyFirst],
                                [
                                    'dlv'. $key => $valData->KSHP_DELQT,
                                    'stock'. $key => $valData->OPN_QT,
                                    'tot'. $key => $valData->OPN_QT - $valData->KSHP_DELQT
                                ]
                            );
                        }
                    } else {
                        $hasil[$keyFirst] = array_merge(
                            $hasil[$keyFirst],
                            [
                                'dlv'. $key => '0',
                                'stock'. $key => '0',
                                'tot'. $key => '0'
                            ]
                        );
                    }
                }

                // Outside of first date data
                $getListDataO = $this->getData($valuePeriod->format('Y-m-d'), '', $listItem);
                
                $startCol = 4;
                foreach ($getListDataO as $keyO => $valueO) {
                    $setCols = [];
                    for ($i=0; $i <= $key; $i++) { 
                        if ($i !== $key) {
                            $setCols = array_merge(
                                $setCols,
                                [
                                    'dlv'. $i => 0,
                                    'stock'. $i => 0,
                                    'tot'. $i => 0,
                                    '1'. $i => '11.00  - 13.00  AM',
                                    '2'. $i => 'AM'
                                ]
                            );
                        } else {
                            $setCols = array_merge(
                                $setCols,
                                [
                                    'dlv'. $i => $valueO->KSHP_DELQT,
                                    'stock'. $i => $valueO->OPN_QT,
                                    'tot'. $i => $valueO->OPN_QT - $valueO->KSHP_DELQT,
                                ]
                            );
                        }
                    }

                    $hasil[] = array_merge([
                        'no' => count($hasil) + 1,
                        'part' => $valueO->KSHP_ITMCD,
                        'name' => $valueO->MITM_SPTNO,
                    ], $setCols);
                }
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

                // Style Header
                $event->sheet->getStyle('A1:A2')->applyFromArray([
                    'font' => [
                        'size' => '14',
                        'bold' => true,
                        'italic' => true,
                        'underline' => true
                    ]
                ]);

                $event->sheet->getStyle('A3:'.$highestColumn.'4')->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ]
                ]);

                // Buat Table
                $event->sheet->styleCells(
                    'A3:'.$highestColumn.$highestRow,
                    [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            ],
                        ]
                    ]
                );

                // Merge first 3 cols
                $event->sheet->getDelegate()->mergeCells('A3:A4');
                $event->sheet->getDelegate()->mergeCells('B3:B4');
                $event->sheet->getDelegate()->mergeCells('C3:C4');

                $event->sheet->getStyle('A3:'.$highestColumn.'4')->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle('A3:'.$highestColumn.'4')->getAlignment()->setVertical('center');

                // Merge Date
                $startCol = 3;
                foreach ($this->getArrPeriod() as $key => $valuePeriod) {
                    $event->sheet->getDelegate()->mergeCells($this->toAlpha($startCol).'3:'.$this->toAlpha($startCol + 2).'3');
                    $event->sheet->getStyle($this->toAlpha($startCol).'5:'.$this->toAlpha($startCol + 2).$highestRow)->getNumberFormat()->setFormatCode('#,##0');

                    if ($key !== count($this->getArrPeriod()) - 1) {
                        $event->sheet->getDelegate()->mergeCells($this->toAlpha($startCol + 3).'3:'.$this->toAlpha($startCol + 3).'4');
                        $event->sheet->getDelegate()->mergeCells($this->toAlpha($startCol + 4).'3:'.$this->toAlpha($startCol + 4).'4');
                        
                        $event->sheet->getStyle($this->toAlpha($startCol + 3).'5:'.$this->toAlpha($startCol + 4).$highestRow)->getAlignment()->setHorizontal('center');
                    }
                    $startCol = $startCol + 5;
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

    function getData($date, $item = '', $itemException = [])
    {
        $dataPrep = DB::connection('sqlsrv_mega_exim')->table('Z_STXI_YPO_DAILY_CONF')
            ->where('KSHP_BSGRP', 'SME3IIZMRI')
            ->where('KSHP_SHPDT', $date);

        if (!empty ($item)) {
            $dataPrep->where('KSHP_ITMCD', $item);
        }

        if (count($itemException) > 0) {
            $dataPrep->whereNotIn('KSHP_ITMCD', $itemException);
        }

        return $dataPrep->get();
    }
}
