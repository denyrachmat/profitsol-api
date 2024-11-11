<?php

namespace App\Http\Controllers\STXI\BIM;

use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Excel;
use Carbon\Carbon;
use PDF;
use GuzzleHttp\Psr7;
use DB;

use App\Models\STXI\BIM\CircularTenMstr;
use App\Models\STXI\BIM\CircularTenModelDet;
use App\Models\STXI\BIM\CircularTenPathHtm;

use App\Imports\STXI\BIM\ImportCircularTen;
use App\Imports\STXI\BIM\ImportTENList;

use App\Jobs\STXI\BIM\SyncCirTentoOldDMS;
use App\Jobs\STXI\BIM\SyncActionCirten;
use Redis;

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

                // $submit = SyncActionCirten::dispatch($value['tenNum'], $value['tenNumEpson'], $filehtm, $file)->onQueue('SyncCirTentoOldDMS');

                $importer = new ImportCircularTen(
                    $value['tenNum'],
                    $filehtm,
                    $value['tenNumEpson'],
                    2,
                    $file,
                    $request->has('username') ? $request->username : 'deny-rachmat@sumitronics.co.jp'
                );

                Excel::import($importer, $file, 'ten_bim');

                // Send To DMS
                if (!empty($importer->data) && isset($importer->data) && isset($importer->data['send_data']) && !empty($importer->data['send_data'])) {
                    SyncCirTentoOldDMS::dispatch($importer->data['send_data'])->onQueue('SyncCirTentoOldDMS');

                    Redis::publish('portalv2', json_encode([
                        'app' => 'cirten',
                        'message' => 'TEN ' . $value['tenNum'] . ' : Upload on progress !',
                        'type' => 'green',
                        'status' => 'start',
                        'data' => [
                            'secTenNo' => $value['tenNum'],
                            'epsTenNo' => $value['tenNumEpson'],
                            'HTMLPath' => $filehtm,
                            'excelPath' => $file
                        ],
                        'cek' => $importer
                    ]));
                } else {
                    Redis::publish('portalv2', json_encode([
                        'app' => 'cirten',
                        'message' => 'TEN ' . $value['tenNum'] . ' : Excel data of ten not found, please check it !',
                        'type' => 'red',
                        'status' => 'failed',
                        'data' => [
                            'secTenNo' => $value['tenNum'],
                            'epsTenNo' => $value['tenNumEpson'],
                            'HTMLPath' => $filehtm,
                            'excelPath' => $file
                        ],
                        'cek' => $importer
                    ]));
                }

                $hasil[] = [
                    'status' => true,
                    'files' => $importer
                ];
            } else {
                Redis::publish('portalv2', json_encode([
                    'app' => 'cirten',
                    'message' => 'TEN ' . $value['tenNum'] . ' : Failed to get data !',
                    'type' => 'red',
                    'status' => 'failed',
                    'data' => [
                        'secTenNo' => $value['tenNum'],
                        'epsTenNo' => $value['tenNumEpson'],
                        'HTMLPath' => $filehtm,
                        'excelPath' => $file
                    ]
                ]));

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
    public function resubmitCirten(string $id, string $username = '')
    {
        $cirtenMstr = CircularTenMstr::where('CIRTEN_NO', $id)->first();

        if (!empty($cirtenMstr)) {
            // $submit = SyncActionCirten::dispatch($id, $cirtenMstr->CIRTEN_TENIEI, $cirtenMstr->CIRTEN_HTMFILEPATH, $cirtenMstr->CIRTEN_FILEPATH)->onQueue('SyncCirTentoOldDMS');

            $importer = new ImportCircularTen($id, $cirtenMstr->CIRTEN_HTMFILEPATH, $cirtenMstr->CIRTEN_TENIEI, 2, $cirtenMstr->CIRTEN_FILEPATH, $username);

            Excel::import($importer, $cirtenMstr->CIRTEN_FILEPATH, 'ten_bim');

            // return $this->handleError('Re-sync TEN ' . $id . ' Failed', $importer);
            // return $importer->data;
            if (!empty($importer->data) && isset($importer->data) && isset($importer->data['send_data']) && !empty($importer->data['send_data'])) {
                SyncCirTentoOldDMS::dispatch($importer->data['send_data'])->onQueue('SyncCirTentoOldDMS');

                Redis::publish('portalv2', json_encode([
                    'app' => 'cirten',
                    'message' => 'TEN ' . $id . ' : Upload on progress !',
                    'type' => 'green',
                    'status' => 'start',
                    'data' => [
                        'secTenNo' => $id,
                        'epsTenNo' => $cirtenMstr->CIRTEN_TENIEI,
                        'HTMLPath' => $cirtenMstr->CIRTEN_HTMFILEPATH,
                        'excelPath' => $cirtenMstr->CIRTEN_FILEPATH
                    ],
                    'cek' => $importer
                ]));

                return $this->handleResponse($importer, 'Re-sync TEN ' . $id . ' On progress');
            } else {
                Redis::publish('portalv2', json_encode([
                    'app' => 'cirten',
                    'message' => 'TEN ' . $id . ' : Excel data of ten not found, please check it !',
                    'type' => 'red',
                    'status' => 'failed',
                    'data' => [
                        'secTenNo' => $id,
                        'epsTenNo' => $cirtenMstr->CIRTEN_TENIEI,
                        'HTMLPath' => $cirtenMstr->CIRTEN_HTMFILEPATH,
                        'excelPath' => $cirtenMstr->CIRTEN_FILEPATH
                    ],
                    'cek' => $importer
                ]));

                return $this->handleError('Re-sync TEN ' . $id . ' Failed', $importer);
            }
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
                $cekData = CircularTenMstr::where('CIRTEN_NO', $tenNum[1])
                    ->orwhere('CIRTEN_NO', explode(' ', $getFolderName)[0])
                    ->whereNotNull('CIRTEN_DMS_DOC_ID')
                    ->first();

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
        $importer1 = new ImportTENList($year, 1);

        Excel::import($importer1, 'Technical Notice List/TECHNICAL NOTICE LIST - PRINTER IEI.xlsx', 'root_bim');

        $importer2 = new ImportTENList($year, 0);

        Excel::import($importer2, 'Technical Notice List/TECHNICAL NOTICE LIST - PROJECTOR.xlsx', 'root_bim');

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
            $importer = new ImportCircularTen($ten, $getData->CIRTEN_HTMFILEPATH, $getData->CIRTEN_TENIEI, 3, $getData->CIRTEN_FILEPATH);

            Excel::import($importer, $getData->CIRTEN_FILEPATH, 'ten_bim');

            $pdf = Pdf::loadView('STXI/BIM/circularTenLayout', $importer->dataForPDF);

            return $pdf->download($ten . '.pdf');
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

    public function cekFilePDF($ten)
    {
        $url = 'http://192.168.100.32/public/storage/circular_ten/' . $ten . '/' . $ten . '.pdf';

        return Psr7\Utils::tryFopen($url, 'r');
    }

    public function viewListItemDesc($ten)
    {
        $data = CircularTenMstr::select(
            DB::raw('CIM_ITMCD as MDLCD'),
            DB::raw('MITM_ITMD1 as [DESC]'),
            DB::raw('MITM_ITMD2'),
            DB::raw('MITM_STKUOM'),
            DB::raw('MITM_SPTNO as PARTNO'),
            'MITM_ITMTY',
            'MITM_SUPCD'
        )
            ->where('CIRTEN_NO', base64_decode($ten))
            ->join('CIRTEN_ITM_DET', 'CIRTEN_ITM_DET.CM_ID', 'CIRTEN_MSTR.id')
            ->join('MGSVR.VMI_SME.dbo.MITM_TBL', 'MITM_ITMCD', 'CIM_ITMCD')
            ->orderby('CIRTEN_MSTR.created_at', 'desc')
            ->get();


        return $data;
    }
}
