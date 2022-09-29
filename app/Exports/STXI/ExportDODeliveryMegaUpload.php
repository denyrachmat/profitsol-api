<?php

namespace App\Exports\STXI;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExportDODeliveryMegaUpload implements FromCollection, WithHeadings
{
    private $data;

    public function __construct($data, $WH)
    {
        $this->data = $data;
        $this->WH = $WH;
    }

    public function headings(): array
    {
        return [
            [
                'Costumer Code',
                'TYD261R'
            ],
            [
                'Warehouse',
                $this->WH
            ],
            [
                'Delivery Code',
                'SMT100U'
            ],
            [],
            [
                'Item Code',
                'Delivery Date',
                'D/N No',
                'Delivery No',
                'Quantity',
                'Unit Price'
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
            # code...
        }

        return collect($hasil);
    }
}
