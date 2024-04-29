<?php

namespace App\Imports\STXI\EMS2;

use Illuminate\Process\Pipe;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\STXI\EMS2\TYOA_BC_MSTR;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

use App\Jobs\STXI\EMS2\AutoFillTYOWebEdiQueue;

class ImportTYOAutoBCCreator implements ToModel, WithStartRow
{
    public function getID() {
        
        $getLastPO = TYOA_BC_MSTR::where(DB::raw('YEAR(TYOAM_DLVDT)'), date('y'))
            ->where(DB::raw('MONTH(TYOAM_DLVDT)'), date('m'))
            ->orderBy('created_at', 'desc')
            ->first();

        return 'TYO-ABC-' . (empty($getLastPO) ? (date('y/m/d') . '/' . '0001') : date('y/m/d') . '/' . sprintf('%04d', (int) substr($getLastPO->YPO_TXID, -3) + 1));
    }

    public function startRow(): int
    {
        return 2;
    }

    public function model(array $row)
    {
        date_default_timezone_set('Asia/Jakarta');
        ini_set("memory_limit", "4G");
        if (!empty($row[0]) && !empty($row[1])) {
            $DLVDT = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[7])->format('Y/m/d');
            Storage::disk('public')->put('data_forpy.json', json_encode([[
                'po_no' => $row[1],
                'date' => $DLVDT,
                'qty' => $row[2],
                'spq' => $row[3],
                'job_no' => $row[6],
            ]]));

            $url = Storage::disk('public')->url('data_forpy.json');

            $insertJob = (
                new AutoFillTYOWebEdiQueue(
                    $row,
                    $url,
                    $DLVDT,
                    $this->getID()
                )
            );

            TYOA_BC_MSTR::updateorcreate([
                'TYOAM_PONO' => $row[1],
                'TYOAM_DLVDT' => $DLVDT,
            ], [
                'TYOA_ID' => $this->getID(),
                'TYOAM_PONO' => $row[1],
                'TYOAM_ITMCD' => $row[0],
                'TYOAM_QTY' => $row[2],
                'TYOAM_JOBNO' => $row[6],
                'TYOAM_DLVDT' => $DLVDT,
                'TYOAM_STAT' => 3,
                'TYOAM_REMARKS' => 'On Queue, please wait.',
                'TYOAM_SPQ' => $row[3]
            ]);
    
            dispatch($insertJob)->onQueue('autoFillTYO');
        }
    }
}
