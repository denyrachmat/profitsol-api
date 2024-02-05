<?php

namespace App\Imports\STXI\BIM;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;

class ImportCircularTen implements ToModel
{
    public $data;
    /**
    * @param Collection $collection
    */
    public function model(array $row)
    {
        $this->data = $row;
    }
}
