<?php

namespace App\Imports\STXI\LOG;

use Illuminate\Support\Collection;
// use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Imports\STXI\LOG\Ceisa40\ImportHeader;
use App\Imports\STXI\LOG\Ceisa40\ImportDokumen;
use App\Imports\STXI\LOG\Ceisa40\ImportBarang;
use App\Imports\STXI\LOG\Ceisa40\ImportEntitas;

class ImportCeisa40 implements WithMultipleSheets
{
    private $incout;

    public function __construct($incout)
    {
        $this->incout = $incout;
    }
    /**
    * @param Collection $collection
    */
    public function sheets(): array
    {
        return [
            'HEADER' => new ImportHeader($this->incout),
            'ENTITAS' => new ImportEntitas($this->incout),
            'BARANG' => new ImportBarang($this->incout),
            'DOKUMEN' => new ImportDokumen($this->incout),
        ];
    }
}
