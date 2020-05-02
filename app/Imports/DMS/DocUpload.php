<?php

namespace App\Imports\DMS;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class DocUpload implements ToCollection
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        //
    }
}
