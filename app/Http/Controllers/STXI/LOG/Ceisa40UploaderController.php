<?php

namespace App\Http\Controllers\STXI\LOG;

use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Http\Request;
use Excel;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use App\Imports\STXI\LOG\ImportCeisa40;
use App\Jobs\STXI\LOG\SyncITInventoryQueue;

use App\Traits\STXI\LOG\Ceisa40Traits;

class Ceisa40UploaderController extends BaseController
{
    use Ceisa40Traits;
    public function uploadData(Request $req)
    {
        ini_set('max_execution_time', '300');
        // $nama_file = $req->file->hashName();
        $file = new File($req->file);
        $extNya = $req->file('file')->getClientOriginalExtension();
        $realFileName = $req->file('file')->getClientOriginalName();

        $fileHash = str_replace('.' . $file->extension(), '', $file->hashName());
        $nama_file = $fileHash . '.' . $extNya;

        // return $nama_file;

        $req->file->storeAs('/public/upload_ceisa40/', $nama_file);

        if ($extNya == 'xls') {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $writer = new Xlsx($spreadsheet);
            $nama_file = $fileHash . '.xlsx';
            $writer->save('/public/upload_ceisa40/' . $nama_file);
        }


        if (str_contains($realFileName, '1.6') || str_contains($realFileName, '2.7I') || str_contains($realFileName, '4.0')) {
            logger('ini incoming !!');
            $state = 'INC';
        } else {
            $state = 'OUT';
        }

        $importer = new ImportCeisa40($state);

        Excel::import($importer, public_path('/storage/upload_ceisa40/' . $nama_file));

        return $this->handleResponse([], 'Upload Sukses ' . $nama_file);
    }

    public function syncCeisaToWebBased($noAju, $bc, $id){
        try {
            ini_set('max_execution_time', '3200');
            $downloadExcel = $this->downloadExcel($noAju, $bc, $id, false);
            
            if (str_contains($downloadExcel, '1.6') || str_contains($downloadExcel, '2.7I') || str_contains($downloadExcel, '4.0')) {
                logger('ini incoming !!');
                $state = 'INC';
            } else {
                $state = 'OUT';
            }
    
            $importer = new ImportCeisa40($state);
    
            Excel::import($importer, public_path($downloadExcel));
    
            return $this->handleResponse([], 'Sync data sukses !!');
        } catch (\Throwable $th) {
            return $this->handleError($th->getMessage());
        }
    }

    public function test($db, $data) {
        Redis::set($db, $data);
    }

    public function autoSyncCeisa40() {
        $getNGData = DB::connection('sqlsrv_itinv')->table('v_empty_cols')->get();

        foreach ($getNGData as $key => $value) {
            SyncITInventoryQueue::dispatch(date('Y-m-01'), date('Y-m-t'))->onQueue('syncCeisa40ITInventory');
        }

        return $this->handleResponse($getNGData, 'Sync data queued !!');
    }

    public function syncByDate($fdate, $ldate, $isSyncMega = false){
        $begin = new \DateTime($fdate);
        $end = new \DateTime(date('Y-m-d H:i:s', strtotime($ldate . ' +1 day')));
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($begin, $interval, $end);

        // return $this->handleResponse($period, 'Sync data queued !!');
        $sync = [];

        if ($fdate !== $ldate) {
            foreach ($period as $key => $value) {
                $sync[] = $value->format("Y-m-d");
                
                SyncITInventoryQueue::dispatch($value->format("Y-m-d"), $value->format("Y-m-d"), $isSyncMega)->onQueue('SyncITInventoryQueue');
            }
        } else {
            $sync[] = $fdate;
                
            SyncITInventoryQueue::dispatch($fdate, $fdate, $isSyncMega)->onQueue('SyncITInventoryQueue');
        }

        return $this->handleResponse($sync, 'Sync data queued !!');
    }
}
