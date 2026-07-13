<?php

namespace App\Exports\STXI\TOS;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ExportQuizTemplate implements FromCollection, WithHeadings, WithTitle
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return collect([]);
    }

    public function headings(): array
    {
        return [
            'Question',
            'Option A',
            'Option B',
            'Option C',
            'Option D',
            'Correct Answer',
        ];
    }

    public function title(): string
    {
        return 'Quiz Template';
    }
}
