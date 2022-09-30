<?php

namespace App\Exports\STXI;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;

class ExportDODelivery implements WithMultipleSheets
{
    use Exportable;
    private $data;

    public function __construct($data, $date)
    {
        $this->data = $data;
        $this->date = $date;
    }

    public function sheets(): array
    {
        $dataASGL = array_filter($this->data, function($f) { return substr($f['MITM_MODELCD'],0) == 'F'; });
        $dataDMISL = array_filter($this->data, function($f) { return substr($f['MITM_MODELCD'],0) == 'E'; });
        $sheets = [
            'FIFO List' => new ExportDOFifo($this->data, $this->date),
            'Upload ASGL FG' => new ExportDODeliveryMegaUpload($this->data, 'ASGL FG'),
            'Upload DMISL' => new ExportDODeliveryMegaUpload($dataDMISL, 'DMISL')
        ];

        return $sheets;
    }
}
