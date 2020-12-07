<?php

namespace App\Exports\HRMS;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

use App\Exports\HRMS\trainingRecapValue;
use App\Exports\HRMS\trainingStatisticValue;

class exportTrainingValue implements WithMultipleSheets
{
    use Exportable;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        $sheets = [
            new trainingRecapValue($this->data, 'Recapitulation'),
            new trainingStatisticValue($this->data, 'Statistics')
        ];

        return $sheets;
    }
}
