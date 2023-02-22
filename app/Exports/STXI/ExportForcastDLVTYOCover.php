<?php

namespace App\Exports\STXI;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;

class ExportForcastDLVTYOCover implements WithMultipleSheets
{
    use Exportable;
    private $data, $dataSummary;

    public function __construct($data, $dataSummary)
    {
        $this->data = $data;
        $this->dataSummary = $dataSummary;
    }

    public function sheets(): array
    {
        $sheets = [
            'FC' => new ExportForcastDLVTYO($this->data),
            'Summary' => new ExportForcastDLVTYOSummary($this->dataSummary),
        ];

        return $sheets;
    }
}
