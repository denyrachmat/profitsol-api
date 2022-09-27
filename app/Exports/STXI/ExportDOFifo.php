<?php

namespace App\Exports\STXI;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExportDOFifo implements FromCollection, WithHeadings
{

    private $data;

    public function __construct($data, $date)
    {
        $this->data = $data;
        $this->date = $date;
    }

    public function headings(): array
    {
        return [
            [
                'Plan Delivery Tanggal '.date('d M Y', strtotime($this->date))
            ],
            [
                'No',
                'Model',
                'Description',
                'Qty Delivery',
                'Barcode Remarks',
                'Delivery No',
                'Qty',
                'Box Count',
                'Total Qty'
            ]
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $hasil = [];

        foreach ($this->data as $key => $value) {
            if (count($value['SPQ_FET']) > 0) {
                foreach ($value['SPQ_FET'] as $keyDet => $valueDet) {
                    $hasil[] = [
                        'no' => $keyDet == 0 ? $key + 1 : '',
                        'MITM_MODELCD'=> $keyDet == 0 ? $value['MITM_MODELCD'] : '',
                        'MITM_ITMD1'=> $keyDet == 0 ? $value['MITM_ITMD1'] : '',
                        'QTY'=> $keyDet == 0 ? $value['TOT_OUT_BC_DLV'] + $value['TOT_OUT_STOCK_DLV'] : '',
                        'BARCODE_ITER'=> $keyDet == 0 || $valueDet['BARCODE_REMARKS'] != $value['SPQ_FET'][$keyDet - 1]['BARCODE_REMARKS'] ? $valueDet['BARCODE_REMARKS'] : '',
                        'DRD_DELNO'=> $keyDet == 0 || $valueDet['DRD_DELNO'] != $value['SPQ_FET'][$keyDet - 1]['DRD_DELNO'] ? $valueDet['DRD_DELNO'] : '',
                        'DRD_QTY'=> $valueDet['DRD_QTY'],
                        'COUNT_BOX' => $valueDet['BOX_COUNT'],
                        'TOTAL' => $valueDet['DRD_QTY'] * $valueDet['BOX_COUNT']
                    ];
                    // foreach ($valueDet as $keySPQ => $valueSPQ) {
                    //     $hasil[] = [
                    //         'no' => $keySPQ == 0 ? $key + 1 : '',
                    //         'MITM_MODELCD'=> $keySPQ == 0 ? $value['MITM_MODELCD'] : '',
                    //         'MITM_ITMD1'=> $keySPQ == 0 ? $value['MITM_ITMD1'] : '',
                    //         'QTY'=> $keySPQ == 0 ? $value['TOT_OUT_BC_DLV'] + $value['TOT_OUT_STOCK_DLV'] : '',
                    //         // 'BARCODE_ITER'=> $keySPQ == 0 ? $keyDet : '',
                    //         'DRD_DELNO'=> $valueSPQ['DRD_DELNO'],
                    //         'DRD_QTY'=> $valueSPQ['DRD_QTY'],
                    //         'COUNT_BOX' => $valueSPQ['COUNT_BOX'],
                    //         'TOTAL' => $valueSPQ['TOTAL']
                    //     ];
                    // }
                }
            }
        }

        return collect($hasil);
    }
}
