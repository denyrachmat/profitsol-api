<?php

namespace App\Http\Controllers\STXI\LOG;

use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Http\Request;
use App\Imports\STXI\LOG\ImportHSCodeForm;
use Excel;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Http;
use PDF;
use Illuminate\Support\Facades\DB;

use App\Models\STXI\LOG\HSCodeUplMaster;
use App\Models\STXI\LOG\HSCodeGroupBeaDetail;

use App\Exports\STXI\LOG\ExportHSCodeReport;
use App\Traits\AMS\ApprovalActionTraits;
use App\Http\Requests\AMS\ApprovalRunningApproveActionRequest;
use App\Jobs\STXI\LOG\ExportHSCodeQueue;

class HSCodeUploadController extends BaseController
{
    use ApprovalActionTraits;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // return HSCodeGroupBeaDetail::whereNull('HSCD_BEAGRP_PRNT')->with('childGroup.intrDocBea')->get();
        return HSCodeUplMaster::join('CRPTWEB.dbo.VIEW_MITM_TBL', 'MITM_ITMCD', 'HSCD_ITMCD')->get();
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
        ini_set('max_execution_time', '300');
        // $nama_file = $req->file->hashName();
        $file = new File($request->file);
        $extNya = $request->file('file')->getClientOriginalExtension();

        $fileHash = str_replace('.' . $file->extension(), '', $file->hashName());
        $nama_file = $fileHash . '.' . $extNya;

        // return $nama_file;
        $request->file->storeAs('/public/upload_hs_code_form/', $nama_file);

        if ($extNya == 'xls') {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $writer = new Xlsx($spreadsheet);
            $nama_file = $fileHash . '.xlsx';
            $writer->save('/public/upload_hs_code_form/' . $nama_file);
        }

        $importer = new ImportHSCodeForm($request->username);

        Excel::import($importer, public_path('/storage/upload_hs_code_form/' . $nama_file));

        return $this->handleResponse([], 'Upload Sukses ' . $nama_file);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return HSCodeGroupBeaDetail::whereNull('HSCD_BEAGRP_PRNT')->with('childApps')->get();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
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
        return HSCodeUplMaster::where('id', $id)->delete();
    }

    public function exportData(Request $request, $withHist = false, $downloadLast = false)
    {
        ExportHSCodeQueue::dispatch($request->filter, $withHist, 'excel', $request->username, $downloadLast)->onQueue('hsCodeDownloader');
        return 'Export in queue, you will be notified when it is ready to download';
    }

    public function autoExportData($withHist)
    {
        ini_set('max_execution_time', '3600');
        ini_set('memory_limit', '2048M');

        $startTime = microtime(true);
        $filter = [
            [
                "cols" => "HSCD_APRVSTAT",
                "param" => "=",
                "value" => "1"
            ]
        ];


        Excel::store(new ExportHSCodeReport($filter, $withHist), 'export_hscode_auto.xlsx', 'public');

        $download = 'storage/export_hscode_auto.xlsx';

        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);

        return 'Done exporting HS Code data in ' . $executionTime . ' seconds, download at ' . url($download);
    }

    public function exportDataWithHistory()
    {
        $filter = [
            [
                "cols" => "HSCD_APRVSTAT",
                "param" => "=",
                "value" => "1"
            ]
        ];

        $datetime = date('y-m-d his');
        Excel::store(new ExportHSCodeReport($filter, true), 'export_hscode_auto.xlsx', 'public');

        $download = 'storage/export_hscode_auto.xlsx';

        return 'Done exporting HS Code data, download at ' . url($download);
    }

    public function exportDataPDF(Request $request)
    {
        ExportHSCodeQueue::dispatch($request->filter, false, 'pdf', $request->username)->onQueue('hsCodeDownloader');
        return 'Export in queue, you will be notified when it is ready to download';
    }

    public function testHeaderData()
    {
        $data = HSCodeGroupBeaDetail::whereNull('HSCD_BEAGRP_PRNT')->with('childGroup.intrDocBea')->get();

        $firstPart = [
            'Part Code',
            'BG',
            'Biz Unit',
            'Item Desc',
            'QC Desc',
            'Maker PN',
            'Maker Name',
            'Series',
            'Maker Recomendation',
            'QC Doc',
            'Approval Date',
            'HS Code',
            'Section',
            'Tarif (%)',
            'PPN (%)',
            'PPH (%)',
            'PPnBM (%)',
            'Cukai (%)',
            'UoM'
        ];

        $getGroupData = HSCodeGroupBeaDetail::whereNull('HSCD_BEAGRP_PRNT')->with('childGroup.intrDocBea')->get()->toArray();

        // return $getGroupData;
        $hasil = $this->headerGroupRecurs($getGroupData, $firstPart);

        return $hasil;
    }

    public function headerGroupRecurs($initData, $initHeader, $rowPos = 0, $colPos = 0, $submitedData = [])
    {
        // Initial recursive for listing init data
        if (count($submitedData) === 0) {
            $submitedData[] = $initHeader;
        }

        // get now array data
        $nowData = current($initData);
        if ($nowData) {
            $totChild = count($nowData['child_group']);
            if ($totChild > 0) { // If child exists
                $initPushArr = $forInitHeader = [];
                $initPushArr[] = $nowData['HSCD_BEADOCNM'];

                for ($i = 0; $i < count($forInitHeader); $i++) {
                    $forInitHeader[] = '';
                }

                $dataDetail = $this->headerGroupRecurs($nowData['child_group'], $forInitHeader, $rowPos + 1, $colPos, $submitedData);
                $submitedData = $dataDetail;

                return $submitedData;
                // foreach ($dataDetail as $keyDet => $valueDet) {
                //     $submitedData[count($submitedData) - 1] = $keyDet === 0
                //     ? array_merge($submitedData[count($submitedData) - 1], $initPushArr)
                //     : array_merge($submitedData[count($submitedData) - 1], ['']);
                // }
            } else {
                return $submitedData;
                next($initData);
                return $this->headerGroupRecurs($initData, $initHeader, $rowPos, $colPos, $submitedData);
            }

            $rowPos++;
        }

        return $submitedData;
    }

    public function HSCodeFilter(Request $request): array
    {
        ini_set('memory_limit', '2048M');
        $data = HSCodeUplMaster::join(DB::raw('CRPTWEB.dbo.VIEW_MITM_TBL as vm'), 'vm.MITM_ITMCD', '=', 'HSCD_ITMCD')
            ->join(DB::raw('(SELECT DISTINCT MITM_ITMCD FROM MGSVR.VMI_DB.dbo.Z_STXI_VW_ITEM_AGE WHERE LATEST_PO_DATE IS NOT NULL AND DAYS_DIFF <= 1095) as zs'), 'zs.MITM_ITMCD', '=', 'HSCD_ITMCD');

        if ($request->has('select')) {
            $data->select($request->select);
        } else {
            $data->select(['HSCD_UPL_TBL.*', 'vm.*']);
        }

        if (
            count($request->filter) > 0 && count(array_filter($request->filter, function ($f) {
                return !empty($f['value']);
            })) > 0
        ) {
            foreach ($request->filter as $key => $value) {
                if (isset($value['value'])) {
                    $data->where($value['cols'], $value['param'], $value['param'] === 'like' ? "%{$value['value']}%" : $value['value']);
                }
            }
        }

        if ($request->has('join')) {
            foreach ($request->join as $keyJoin => $valueJoin) {
                $data->join($valueJoin['table'], $valueJoin['localKey'], $valueJoin['foreignKey']);
            }
        }

        if ($request->has('with')) {
            $data->with($request->with);
        }

        if ($request->has('groupBy')) {
            $data->groupBy($request->select);
        }

        return $data->get()->toArray();
    }

    public function sendApproval(Request $request): array
    {
        $hasilGroup = [];
        foreach ($request->data as $key => $value) {
            $hasilGroup[$value['HSCD_DOCNO']]['HSCD_DOCNO'] = $value['HSCD_DOCNO'];
            $hasilGroup[$value['HSCD_DOCNO']]['det'][] = $value;
        }

        $hasil = [];
        foreach (array_values($hasilGroup) as $keyGrp => $valueGrp) {
            $listDet = [];
            foreach ($valueGrp['det'] as $keyDet => $valueDet) {
                $listDet[] = [
                    'itemcode' => $valueDet['HSCD_ITMCD'],
                    'part_name' => $valueDet['MITM_SPTNO'],
                    'item_desc' => $valueDet['MITM_ITMD1'],
                    'mk_hscode' => $valueDet['HSCD_MKHSCD'],
                    'stxi_hscode' => $valueDet['HSCD_STXICD']
                ];
            }

            $hasil[] = $this->approveAction(new ApprovalRunningApproveActionRequest([
                'username' => $request->username,
                'amsm_id' => 1,
                'stat' => 1,
                'remarks' => 'Sending approval hs code!!',
                'data' => [
                    'HSCD_DOCNO' => $valueGrp['HSCD_DOCNO'],
                    'item_det' => $listDet
                ],
                'onApproval' => [
                    'methods' => 'post',
                    'params' => [
                        'HSCD_DOCNO' => $value['HSCD_DOCNO'],
                    ],
                    'url' => 'http://192.168.100.32/api/div/log/updateApprovalHSCode'
                    // 'url' => 'http://localhost/STX/stx-api/public/api/div/log/updateApprovalHSCode'
                ],
                'onDone' => [
                    'methods' => 'post',
                    'params' => [
                        'HSCD_DOCNO' => $value['HSCD_DOCNO'],
                        'ISDONE' => 1
                    ],
                    'url' => 'http://192.168.100.32/api/div/log/updateApprovalHSCode'
                    // 'url' => 'http://localhost/STX/stx-api/public/api/div/log/updateApprovalHSCode'
                ],
                'msgkey' => 'HSCD_DOCNO'

            ]))->getOriginalContent();
        }

        return $hasil;
    }

    public function updateApprovalHSCode(Request $request)
    {
        // logger(json_encode($request->all()));
        // return $request->all();
        $data = HSCodeUplMaster::where('HSCD_DOCNO', $request->HSCD_DOCNO)
            // ->where('HSCD_ITMCD', $request->HSCD_ITMCD)
            ->update([
                'HSCD_APRVSTAT' => $request->has('ISDONE') && $request->ISDONE == 1 ? 1 : 0,
                'HSCD_REMARK' => $request->approval['remarks'],
                'HSCD_LASTAPPRV' => $request->has('approval') ? $request->approval['username'] : '',
                'HSCD_APPRVDT' => date('Y-m-d H:i:s')
            ]);

        return $this->handleResponse($data, 'Update done !!');
    }
}
