<?php

namespace App\Http\Controllers\STXI\EMS2;

use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\STXI\importSPQMaster;
use Illuminate\Support\Facades\DB;

use App\Models\STXI\EMS2\SPQMaster;

class deliveryMethodToPSIController extends BaseController
{
    public function UploadSPQ(Request $req)
    {
        // return $req->file('file')->hashName();
        $nama_file = $req->file->hashName();

        $req->file->storeAs('/public/upload_spq/', $nama_file);

        $importer = new importSPQMaster();

        Excel::import($importer, public_path('/storage/upload_spq/'. $nama_file));

        return $this->handleResponse([], 'Upload Sukses ' . $nama_file);
    }

    public function SPQIndex()
    {
        $data = SPQMaster::select(
            'id',
            'MITM_MODELCD',
            'MITM_PCBCD',
            DB::raw('CAST(MITM_SPQ AS INT) MITM_SPQ'),
            'STXI_SPQ'
        )
        ->join(
            DB::raw('[MGSVR].[VMI_TYO].[dbo].[MITM_TBL]'), 'MITM_ITMCD', 'MITM_MODELCD'
        )
        // ->where('MITM_MODEL', 1)
        ->get();


        return $this->handleResponse($data, 'Data found !');
    }

    public function searchItemMaster($filter)
    {
        $data = DB::connection('sqlsrv_mega_tyo')->table('MITM_TBL')->where('MITM_ITMCD', 'LIKE', $filter.'%')->get()->toArray();

        if (count($data) > 0) {
            return $this->handleResponse($data, 'Data found !');
        } else {
            return $this->handleError('Data not found !');
        }
    }

    public function SPQCreateUpdate(Request $req)
    {
        $data = SPQMaster::updateOrCreate([
            'MITM_MODELCD' => $req->MITM_MODELCD,
            'MITM_PCBCD' => $req->MITM_PCBCD
        ],[
            'MITM_MODELCD' => $req->MITM_MODELCD,
            'MITM_PCBCD' => $req->MITM_PCBCD,
            'STXI_SPQ' => $req->STXI_SPQ,
        ]);

        return $this->handleResponse($data, 'Data Updated !');
    }

    public function SPQDeleteData($id)
    {
        $data = SPQMaster::where('id', $id)->delete();

        return $this->handleResponse($data, 'Data deleted !');
    }

    public function WithBarcode(Request $data)
    {
    }
}
