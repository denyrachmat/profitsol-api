<?php

namespace App\Imports\STXI\EMS2;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\STXI\EMS2\YPOMaster;
use App\Models\STXI\EMS2\YPOSTXIPODet;

class ImportSTXIYEIDPOConfirmation implements ToModel, WithStartRow, WithCalculatedFormulas
{
    function __construct() {
        $this->isStart = true;
        $this->id = '';
    }

    public function startRow(): int
    {
        return 4;
    }

    /**
    * @param Collection $collection
    */
    public function model(array $row)
    {
        date_default_timezone_set('Asia/Jakarta');
        ini_set("memory_limit", "4G");

        // logger($row);
        if (!empty($row[1]) && !empty($row[2])) {
            // logger([$row[1], $row[2]]);
            if ($this->isStart) {
                logger('masuk change ID');
                $getLatestID = YPOMaster::where('YPO_TXID', 'like', 'YPO-' .date('y/m/d').'%')->orderBy('id', 'desc')->first();

                $this->id = 'YPO-' . (empty($getLatestID) ? (date('y/m/d') . '/' . '0001') : date('y/m/d') . '/' . sprintf('%04d', (int) substr($getLatestID->YPO_TXID, -3) + 1));
                logger([$this->id, $getLatestID]);
            }

            $cekData = YPOMaster::where('YPO_TXID', $this->id)->where('YPO_ITMCD', $row[1])->first();

            if (empty($cekData)) {
                $insertMaster = YPOMaster::create([
                    'YPO_ITMCD' => $row[1],
                    'YPO_REMARKS' => $row[6],
                    'YPO_MRPDT' => isset($row[7]) && is_numeric($row[7]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[7])->format('Y-m-d') : $row[7],
                    'YPO_MAILDT' => isset($row[8]) && is_numeric($row[8]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[8])->format('Y-m-d') : NULL,
                    'YPO_RCVDT' => isset($row[9]) && is_numeric($row[9]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[9])->format('Y-m-d') : NULL,
                    'YPO_PONO' => $row[10],
                    'YPO_PODUEDT' => isset($row[11]) && is_numeric($row[11]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[11])->format('Y-m-d') : $row[11],
                    'YPO_POQTY' => $row[12],
                    'YPO_TXID' => $this->id,
                    'YPO_STXI_PO' => '',
                ]);

                $idMaster = $insertMaster->id;
            } else {
                $idMaster = $cekData->id;
            }
            
            if (!empty($row[14])) {
                YPOSTXIPODet::create([
                    'YMT_ID' => $idMaster,
                    'YSPDT_PONO' => $row[14],
                    'YSPDT_INVNO' => isset($row[17]) ? $row[17] : '',
                    'YSPDT_POQT' => isset($row[16]) ? (int)$row[16] : 0, //GIT Qty
                    'YSPDT_POQTY' => isset($row[20]) ? (int)$row[20] : 0//PO Qty
                ]);
            }

            $this->isStart = false;
        } else {
            logger('masuk sini space kosong');
            $this->isStart = true;
        }
    }
}
