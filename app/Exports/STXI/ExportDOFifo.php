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
                    foreach ($valueDet as $keySPQ => $valueSPQ) {
                        $hasil[] = [
                            'no' => $keySPQ == 0 && $keyDet == 'BARCODE-1' ? $key + 1 : '',
                            'MITM_MODELCD'=> $keySPQ == 0 && $keyDet == 'BARCODE-1' ? $value['MITM_MODELCD'] : '',
                            'MITM_ITMD1'=> $keySPQ == 0 && $keyDet == 'BARCODE-1' ? $value['MITM_ITMD1'] : '',
                            'QTY'=> $keySPQ == 0 && $keyDet == 'BARCODE-1' ? $value['TOT_OUT_BC_DLV'] + $value['TOT_OUT_STOCK_DLV'] : '',
                            'BARCODE_ITER'=> $keySPQ == 0 ? $keyDet : '',
                            'DRD_DELNO'=> $valueSPQ['DRD_DELNO'],
                            'DRD_QTY'=> $valueSPQ['DRD_QTY']
                        ];
                    }
                }
            }
        }

        return collect($hasil);
    }
}
