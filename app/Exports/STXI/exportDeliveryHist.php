<?php

namespace App\Exports\STXT;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStartRow;

class exportDeliveryHist implements FromCollection, WithHeadings, WithStartRow
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function startRow(): int
    {
        return 2;
    }

    public function headings(): array
    {
        return [
            'No',
            'Model Code',
            'DEL QTY FROM SMT',
            'DEL QTY TO ITEC',
            'DEL DATE'
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // return collect($this->data);
        $hasil = [];
        foreach ($this->data as $key => $value) {
            $hasil[] = [
                $key + 1,
                $value['MITM_MODELCD'],
                $value['TOT_INC_DLV'],
                (int)$value['FTRN'] > 0 ? "0" : $value['TOT_OUT_BC_DLV'],
                $value['DEL_DATE']
            ];
        }

        return collect($hasil);
    }
}
