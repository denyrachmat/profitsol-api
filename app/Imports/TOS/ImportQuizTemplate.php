<?php

namespace App\Imports\TOS;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

class ImportQuizTemplate implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {
            // Process each row (e.g., create model)
            // Example: dd($row);
        }
    }

    /**
     * Import the Quiz template file from storage.
     *
     * @return void
     */
    public static function importFromStorage(): void
    {
        Excel::import(new static, 'Quiz template.xlsx', 'local');
    }
}
