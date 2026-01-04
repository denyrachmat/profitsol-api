<?php

namespace App\Exports\STXI\BIM;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;

class ExportMRPSchemeWeekly implements FromCollection, WithHeadings, WithEvents
{
    use Exportable, RegistersEventListeners;

    private $data, $submitedData, $dataPerlist;

    public function __construct($data, $submitedData = [], $dataPerlist = [])
    {
        $this->data = $data;
        $this->submitedData = $submitedData;
        $this->dataPerlist = $dataPerlist;
        $this->resultDate = [];
        $this->resultWeeks = [];
        $this->BGLT = 49;
    }

    public function headings(): array
    {
        $listWStr = [];
        $getDate = [];
        $getMonthYears = [];
        foreach ($this->data['headers'] as $weekIndex => $weekDate) {
            $listWStr[] = "W" . ($weekIndex + 1);
            $getDate[] = date("d", strtotime($weekDate));
            $getMonthYears[] = $weekIndex > 0
                ? (
                    date('M Y', strtotime($this->data['headers'][$weekIndex - 1])) != date("M Y", strtotime($weekDate))
                    ? date("M Y", strtotime($weekDate))
                    : ''
                )
                : date("M Y", strtotime($weekDate));
        }

        $this->resultDate = $getMonthYears;
        $this->resultWeeks = $listWStr;

        $headers = [
            [
                'New MRP scheme (weekly base)'
            ],
            [
                '1st MRP'
            ],
            [
                'MRP Date',
                ':',
                !empty($this->submitedData) ? date('d/m/Y', strtotime($this->submitedData['mrp_date'])) : ''
            ],
            [
                'PO Issue Date',
                ':',
                !empty($this->submitedData) ? date('d/m/Y', strtotime($this->submitedData['first_date'])) : ''
            ],
            [
                'Release P/O date',
                ':',
                !empty($this->submitedData) ? date('d/m/Y', strtotime($this->submitedData['po_rel_date'])) : ''
            ],
            [
                'MRP Cut off',
                ':',
                !empty($this->submitedData) ? date('d/m/Y', strtotime($this->submitedData['mrp_cutoff_date'])) : ''
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
        $startKeysPar = 0;

        logger('Data Weekly', $this->resultWeeks);
        foreach ($this->dataPerlist as $key => $value) {
            $hasil[$startKeysPar][$startKeys] = '';
            $startKeys = $startKeys + 1;
            $hasil[$startKeysPar][$startKeys] = (string) $key;
            $startKeys = $startKeys + 1;
            $hasil[$startKeysPar][$startKeys] = '';
            $startKeys = $startKeys + 1;

            foreach ($this->resultDate as $keyContent => $valueContent) {
                if (isset($value[$keyContent])) {
                    $hasil[$startKeysPar][0] =$this->resultWeeks[$keyContent];
                }

                $hasil[$startKeysPar][$startKeys] = isset($value[$keyContent]) ? 1 : '-';
                $startKeys = $startKeys + 1;
            }

            $startKeys = 0;
            $startKeysPar = $startKeysPar + 1;
        }

        logger("FinalData", $hasil);

        return collect([$hasil]);
    }
}
