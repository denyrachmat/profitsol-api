<?php

namespace App\Exports\STXI;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ExportDODelivery implements WithMultipleSheets
{
    private $data;

    public function __construct($data, $date)
    {
        $this->data = $data;
        $this->date = $date;
    }

    public function sheets(): array
    {
        $sheets = [
            'FIFO List' => new ExportDOFifo($this->data, $this->date),
            'Mega Upload' => new ExportDODeliveryMegaUpload($this->data, $this->date)
        ];

        return $sheets;
    }
}
