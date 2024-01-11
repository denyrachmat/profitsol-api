<?php

namespace App\Exports\STXI\EMS2\YPOManual;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use \PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

use App\Exports\STXI\ExportYPOManual;

class ExportYPOManualHeader implements WithMultipleSheets
{
    use RegistersEventListeners, Exportable;
    private $data;
    public function __construct($data)
    {
        $this->data = $data;
    }
    public function sheets(): array
    {
        return [
            'new' => New ExportYPOManual($this->data['new'], 'new'),
            'reg' => New ExportYPOManual($this->data['reg'], 'reg')
        ];
    }
}
