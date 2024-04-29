<?php

namespace App\Http\Controllers\STXI\EMS2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\File;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Support\Facades\Storage;

use App\Imports\STXI\EMS2\ImportTYOAutoBCCreator;
use App\Models\STXI\EMS2\TYOA_BC_MSTR;
use App\Jobs\STXI\EMS2\AutoFillTYOWebEdiQueue;

class TYOAutoBarcodeController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return TYOA_BC_MSTR::join(DB::raw('MGSVR.VMI_TYO.dbo.MITM_TBL'), 'MITM_ITMCD', 'TYOAM_ITMCD')->get();
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
    public function store(Request $req)
    {
        ini_set('max_execution_time', '300');
        // $nama_file = $req->file->hashName();
        $file = new File($req->file);
        $extNya = $req->file('file')->getClientOriginalExtension();

        $fileHash = str_replace('.' . $file->extension(), '', $file->hashName());
        $nama_file = $fileHash . '.' . $extNya;

        // return $nama_file;

        $req->file->storeAs('/public/upload_tyo_auto_bc_gen/', $nama_file);

        if ($extNya == 'xls') {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $writer = new Xlsx($spreadsheet);
            $nama_file = $fileHash.'.xlsx';
            $writer->save('/public/upload_tyo_auto_bc_gen/'.$nama_file);
        }

        $importer = new ImportTYOAutoBCCreator();

        Excel::import($importer, public_path('/storage/upload_tyo_auto_bc_gen/' . $nama_file));

        return $this->handleResponse([], 'Upload Sukses ' . $nama_file);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
        $getData = TYOA_BC_MSTR::where('id', $id)->first();
        Storage::disk('public')->put('data_forpy.json', json_encode([[
            'po_no' => $getData->TYOAM_PONO,
            'date' => $getData->TYOAM_DLVDT,
            'qty' => $getData->TYOAM_QTY,
            'job_no' => $getData->TYOAM_JOBNO,
            'spq' => $getData->TYOAM_SPQ,
        ]]));

        $url = Storage::disk('public')->url('data_forpy.json');

        TYOA_BC_MSTR::where('id', $id)->update([
            'TYOAM_STAT' => 3
        ]);

        $insertJob = (
            new AutoFillTYOWebEdiQueue(
                [
                    $getData->TYOAM_ITMCD,
                    $getData->TYOAM_PONO,
                    $getData->TYOAM_QTY,
                    $getData->TYOAM_SPQ,
                    '',
                    '',
                    $getData->TYOAM_JOBNO,
                ],
                $url,
                date('Y/m/d', strtotime($getData->TYOAM_DLVDT)),
                $getData->TYOA_ID
            )
        );

        dispatch($insertJob)->onQueue('autoFillTYO');

        return 'Data '.$getData->TYOAM_JOBNO.' and PO '.$getData->TYOAM_PONO.' resubmited!!';
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
