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

    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function headings(): array
    {
        return $this->data['headers'];
    }
    
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        //
    }
}
