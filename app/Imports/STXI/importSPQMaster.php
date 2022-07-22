<?php

namespace App\Imports\STXI;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use App\Models\STXI\EMS2\SPQMaster;
use Maatwebsite\Excel\Concerns\ToModel;

class importSPQMaster implements ToModel, WithStartRow
{
    /**
    * @param Collection $collection
    */
    public function model(array $row)
    {
        SPQMaster::updateOrCreate([
            'MITM_MODELCD' => $row[0],
            'MITM_PCBCD' => $row[1]
        ],[
            'MITM_MODELCD' => $row[0],
            'MITM_PCBCD' => $row[1],
            'STXI_SPQ' => $row[3],
        ]);
    }

    public function startRow(): int
    {
        return 6;
    }
}
