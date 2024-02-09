<?php

namespace App\Http\Controllers\STXI\BIM;

use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Excel;
use Carbon\Carbon;

use App\Models\STXI\BIM\CircularTenMstr;
use App\Models\STXI\BIM\CircularTenModelDet;
use App\Models\STXI\BIM\CircularTenPathHtm;

use App\Imports\STXI\BIM\ImportCircularTen;
use App\Imports\STXI\BIM\ImportTENList;

use App\Jobs\STXI\BIM\SyncCirTentoOldDMS;

class CirtenUpdateController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = CircularTenMstr::orderby('created_at', 'desc')->get();

        $hasil = [];
        foreach ($data as $key => $value) {
            $hasil[] = [
                'ten_no' => $value->CIRTEN_NO,
                'DMS_DOC_ID' => $value->CIRTEN_DMS_DOC_ID,
                'statusflg' => $value->CIRTEN_STATUSFLG,
                'status' => $value->CIRTEN_STATUS,
                'created_at' => $value->created_at
            ];
        }

        return $this->handleResponse($hasil, 'Data found !');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $hasil = [];

        foreach ($request->data as $key => $value) {
            $file = $value['path'] . '/' . $value['tenNum'] . '.xlsx';
            $filehtm = $value['path'] . '/' . $value['tenNumEpson'] . '.htm';

            if (!Storage::disk('ten_bim')->exists($filehtm)) {
                $filehtm = $value['path'] . '/' . $value['tenNumEpson'] . '.html';
            }

            if (Storage::disk('ten_bim')->exists($file) && Storage::disk('ten_bim')->exists($filehtm)) {
                // $files = mb_convert_encoding( Storage::disk('ten_bim')->get($file), 'UTF-8', 'UTF-8');

                $importer = new ImportCircularTen($value['tenNum'], $filehtm, $value['tenNumEpson'], 2, $file);

                Excel::import($importer, $file, 'ten_bim');
                // Send To DMS
                SyncCirTentoOldDMS::dispatch($importer->data['sendData'])->onQueue('SyncCirTentoOldDMS');

                $hasil[] = [
                    'status' => true,
                    'files' => $importer->data
                ];
            } else {
                $hasil[] = [
                    'status' => false,
                    'files' => null
                ];
            }
        }

        return $hasil;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return 'show';
    }

    /**
     * Show the form for editing the specified resource.
     * For Resubmit Data
     */
    public function resubmitCirten(string $id)
    {
        $cirtenMstr = CircularTenMstr::where('CIRTEN_NO', $id)->first();

        if (!empty($cirtenMstr)) {
            $importer = new ImportCircularTen($id, $cirtenMstr->CIRTEN_HTMFILEPATH, $cirtenMstr->CIRTEN_TENIEI, 2, $cirtenMstr->CIRTEN_FILEPATH);

            Excel::import($importer, $cirtenMstr->CIRTEN_FILEPATH, 'ten_bim');

            SyncCirTentoOldDMS::dispatch($importer->data['send_data'])->onQueue('SyncCirTentoOldDMS');

            return $this->handleResponse([], 'Sync TEN ' . $id . ' On progress');
        } else {
            return $this->handleError('TEN ' . $id . ' not found !!!', []);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function showByDateTen($date)
    {
        $getYear = date('Y', strtotime($date));
        $getMonth = date('m', strtotime($date));
        $listData = Storage::disk('ten_bim')->directories($getYear . '/' . $getMonth);

        $hasil = [];
        foreach ($listData as $key => $value) {
            preg_match('#\((.*?)\)#', $value, $tenNum);

            $getListFolderName = explode('/', $value);
            $getFolderName = $getListFolderName[count($getListFolderName) - 1];

            if (isset($tenNum[1])) {
                $cekData = CircularTenMstr::where('CIRTEN_NO', $tenNum[1])->orwhere('CIRTEN_NO', explode(' ', $getFolderName)[0])->whereNotNull('CIRTEN_DMS_DOC_ID')->first();
                if (empty($cekData)) {
                    $hasil[] = [
                        'foldername' => $getFolderName,
                        'tenNum' => $tenNum[1],
                        'tenNumEpson' => explode(' ', $getFolderName)[0],
                        'path' => $value,
                    ];
                }
            }
        }

        return $hasil;
    }

    public function syncTenList($year)
    {
        $importer1 = new ImportTENList($year);

        Excel::import($importer1, 'ten list/TEN LIST - PRINTER IEI.xlsx', 'ten_bim');

        return 'Sync !!';
    }

    public function generateDocument($ten)
    {
        $getData = CircularTenMstr::where('CIRTEN_NO', $ten)
            ->whereNotNull('CIRTEN_HTMFILEPATH')
            ->whereNotNull('CIRTEN_FILEPATH')
            ->whereNotNull('CIRTEN_TENIEI')
            ->first();

        if (!empty($getData)) {
            $importer = new ImportCircularTen($ten, $getData->CIRTEN_HTMFILEPATH, $getData->CIRTEN_TENIEI, 1, $getData->CIRTEN_FILEPATH);

            Excel::import($importer, $getData->CIRTEN_FILEPATH, 'ten_bim');

            return $importer->pdf;
        } else {
            return $this->handleError('Data not found !', []);
        }
    }

    public function cekViewPrint($ten)
    {
        $getData = CircularTenMstr::where('CIRTEN_NO', $ten)
            ->whereNotNull('CIRTEN_HTMFILEPATH')
            ->whereNotNull('CIRTEN_FILEPATH')
            ->whereNotNull('CIRTEN_TENIEI')
            ->first();

        if (!empty($getData)) {
            $importer = new ImportCircularTen($ten, $getData->CIRTEN_HTMFILEPATH, $getData->CIRTEN_TENIEI, 3, $getData->CIRTEN_FILEPATH);

            Excel::import($importer, $getData->CIRTEN_FILEPATH, 'ten_bim');

            return View('STXI/BIM/circularTenLayout', $importer->dataForPDF);
        } else {
            return $this->handleError('Data not found !', []);
        }
    }
}
