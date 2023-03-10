<?php

namespace App\Imports\STXI\EMS2;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\STXI\EMS2\DLVTYODet;
use App\Models\STXI\EMS2\DLVTYOHist;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithStartRow;
class ImportFIFODOTYO implements ToModel, WithStartRow
{
    /**
    * @param Collection $collection
    */
    private $date;
    private $typeTrans;
    private $hasil = [];
    public function __construct($date, $typeTrans, $hasil = [])
    {
        $this->date = $date;
        $this->typeTrans = $typeTrans;
        $this->hasil = $hasil;
    }

    public function startRow(): int
    {
        return 2;
    }

    public function model(array $row)
    {
        ini_set("memory_limit", "3G");

        if (!empty($row[0])) {
            $masterHist = DLVTYOHist::where('DEL_DATE', $this->date)->where('MITM_MODELCD', $row[0])->where('IO_REMARK', $this->typeTrans)->first();

            if (empty($masterHist)) {
                $this->hasil[] = [
                    'status' => false,
                    'data' => $row,
                    'message' => 'Delivery from '.($this->typeTrans === 'TO_ITEC' ? 'SMT' : 'STOCK').' is not found !!'
                ];
            }

            $dates = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject(intval($row[2]))->format('Y-m-d');
            $dataFIfo = DB::connection('sqlsrv_mega_tyo')
                            ->table("Z_STXI_FIFO_OS_SO('".$row[0]."', ".$row[3].", '".$dates."', '".$dates."', '".$row[1]."')")
                            ->get()
                            ->toArray();
            
            if (count($dataFIfo) > 0 && intVal($dataFIfo[0]->USED_QT) === intVal($row[3])) {
                DLVTYODet::create([
                    'DRST_ID' => $masterHist->id,
                    'DRD_DELNO' => $row[1],
                    'DRD_PRICE' => $dataFIfo[0]->SSO2_SLPRC,
                    'DRD_QTY' => intVal($row[3]),
                    'DRD_DELDT' => $dates,
                ]);

                $this->hasil[] = [
                    'status' => true,
                    'data' => $row,
                    'message' => 'FIFO DO '.$row[1].' successfully assigned to item '.$row[0].' at date '.$dates.'.'
                ];
            } else {
                $this->hasil[] = [
                    'status' => false,
                    'data' => $row,
                    'dataFifo' => $dataFIfo,
                    'message' => 'FIFO DO '.$row[1].' not found to be assigned to item '.$row[0].' at date '.$dates.' !'
                ];
            }
        }
        //
    }

    public function getHasil(): Array
    {
        return $this->hasil;
    }
}
