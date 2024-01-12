<?php

namespace App\Imports\STXI\EMS2;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\ToModel;

use App\Models\STXI\EMS2\YPOMaster;
use App\Models\STXI\EMS2\YPOSTXIPODet;
use Illuminate\Support\Facades\DB;

use App\Jobs\STXI\EMS2\updateInvoiceYPOManualQueue;

class ImportSTXIYEIDPOConfirmation implements ToModel, WithStartRow, WithCalculatedFormulas
{
    protected $type;
    function __construct($type) {
        $this->isStart = true;
        $this->id = '';
        $this->type = $type;
    }

    public function startRow(): int
    {
        return 6;
    }

    /**
    * @param Collection $collection
    */
    public function model(array $row)
    {
        date_default_timezone_set('Asia/Jakarta');
        ini_set("memory_limit", "4G");

        if ($this->type === 'new') {
            $cekLatestReceive = YPOMaster::where('YPO_RCVDT', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[10])->format('Y-m-d'))
                ->where('YPO_TYPE', 'new')
                ->orderBy('id', 'desc')
                ->first();
                
            if (empty($cekLatestReceive)) {
                $this->isStart = true;
            } else {
                $this->isStart = false;
            }
        }

        if (!empty($row[1]) && !empty($row[2])) {
            // logger([$row[1], $row[2]]);
            if ($this->isStart) {
                logger('masuk change ID');
                $getLatestID = YPOMaster::where('YPO_TXID', 'like', 'YPO-' .date('y/m/d').'%')->orderBy('id', 'desc')->first();

                $this->id = 'YPO-' . (empty($getLatestID) ? (date('y/m/d') . '/' . '0001') : date('y/m/d') . '/' . sprintf('%04d', (int) substr($getLatestID->YPO_TXID, -3) + 1));
                logger([$this->id, $getLatestID]);
            }

            $cekData = YPOMaster::where('YPO_TXID', $this->id)->where('YPO_ITMCD', $row[1])->where('YPO_PONO', $row[11])->first();

            if (empty($cekData)) {
                $insertMaster = YPOMaster::create([
                    'YPO_ITMCD' => $row[1],
                    'YPO_REMARKS' => $row[6],
                    'YPO_MRPDT' => isset($row[8]) && !empty($row[8]) && is_numeric($row[8]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[8])->format('Y-m-d') : NULL,
                    'YPO_MAILDT' => isset($row[9]) && !empty($row[9]) && is_numeric($row[9]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[9])->format('Y-m-d') : NULL,
                    'YPO_RCVDT' => isset($row[10]) && !empty($row[10]) && is_numeric($row[10]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[10])->format('Y-m-d') : NULL,
                    'YPO_PONO' => $row[11],
                    'YPO_PODUEDT' => isset($row[16]) && !empty($row[16]) && is_numeric($row[16]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[16])->format('Y-m-d') : $row[16],
                    'YPO_POQTY' => is_numeric($row[13]) ? $row[13] : 0,
                    'YPO_TXID' => $this->id,
                    'YPO_STXI_PO' => '',
                    'YPO_TYPE' => $this->type
                ]);

                $idMaster = $insertMaster->id;
            } else {
                YPOMaster::where('YPO_TXID', $this->id)->where('YPO_ITMCD', trim($row[1]))->where('YPO_PONO', trim($row[11]))->update([
                    'YPO_POQTY' => $cekData->YPO_POQTY + (is_numeric($row[13]) ? $row[13] : 0),
                ]);

                $idMaster = $cekData->id;
            }
            
            if (!empty($row[13])) {
                YPOSTXIPODet::updateOrCreate([
                    'YMT_ID' => $idMaster,
                    'YSPDT_PONO' => $row[15],
                ],[
                    'YMT_ID' => $idMaster,
                    'YSPDT_PONO' => $row[15],
                    'YSPDT_INVNO' => null,
                    'YSPDT_POQT' => isset($row[18]) ? (int)$row[13] - (int)$row[17] : 0, //GIT Qty
                    'YSPDT_POQTY' => isset($row[18]) ? (int)$row[13] - (int)$row[17] : 0, //PO Qty
                    'YSPDT_HSCD' => $row[19]
                ]);
            }

            $this->isStart = false;
        } else {
            logger('masuk sini space kosong');
            $this->isStart = true;

            updateInvoiceYPOManualQueue::dispatch()->onQueue('updateInvoiceYPOManualQueue');
        }
    }

    public function cekData()
    {
        $data = YPOSTXIPODet::join('YPO_MSTR_TBL', 'YMT_ID', 'YPO_MSTR_TBL.id')->whereNull('YSPDT_INVNO')->get();

        if (count($data) > 0) {

            $hasil = [];
            foreach ($data as $key => $value) {    
                $dataView = DB::connection('sqlsrv_ems2')->table('V_YPO_OS_GIT')
                    ->where('PPO1_PONO', $value->YSPDT_PONO)
                    ->where('PPO2_ITMCD', $value->YPO_ITMCD)
                    ->where('PGIT_RCVQT', '>=', $value->YSPDT_POQTY)
                    ->orderBy('PPO1_ISUDT', 'asc')
                    ->first();
                
                if (!empty($dataView)) {
                    $hasil[] = YPOSTXIPODet::where('YMT_ID', $value->YMT_ID)
                        ->where('YSPDT_PONO', $value->YSPDT_PONO)
                        ->update([
                            'YSPDT_INVNO' => $dataView->PGIT_SUPNO,
                            'YSPDT_POQT' => (int)$dataView->PGIT_RCVQT
                        ]);
                }
            }

            if (count($hasil) > 0) {
                return 'update';
            }

            return 'tidak update';
        }        

        return 'tidak update';
    }
}
