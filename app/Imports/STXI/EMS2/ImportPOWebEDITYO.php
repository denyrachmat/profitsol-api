<?php

namespace App\Imports\STXI\EMS2;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\STXI\EMS2\TYO_PO_MSTR;

class ImportPOWebEDITYO implements ToModel
{
    /**
     * @param Collection $collection
     */
    public function model(array $row)
    {
        ini_set("memory_limit", "3G");

        if (!empty($row)) {
            TYO_PO_MSTR::updateOrCreate([
                'TPM_ITMCD' => $row[8],
                'TPM_DLVDT' => date('Y-m-d', strtotime($row[14])),
                'TPM_ORDERNO' => $row[7],
            ],[
                'TPM_ITMCD' => $row[8],
                'TPM_ORDERNO' => $row[7],
                'TPM_DLVDT' => date('Y-m-d', strtotime($row[14])),
                'TPM_STATUS' => $row[0],
                'TPM_ORDERQTY' => (int)$row[69],
                'TPM_CSVOUTDT' => date('Y-m-d H:i:s', strtotime($row[80])),
                'TPM_ORDER_CRTDT' => date('Y-m-d H:i:s', strtotime($row[81])),
                'TPM_ORDER_REGDT' => date('Y-m-d H:i:s', strtotime($row[82])),
                'TPM_PRC' => (float)$row[91],
                'TPM_RPLY_DEADLNDT' => date('Y-m-d', strtotime($row[94])),
            ]);
        }
    }
}