<?php

namespace App\Http\Controllers\STXI\EMS2;

use Illuminate\Http\Request;
use App\Imports\STXI\importRawPO;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\API\PORTAL\BaseController;
use App\Models\STXI\EMS2\FRCST_PO_MRI;
use Illuminate\Support\Facades\DB;

use App\Exports\STXI\ExportPOSummary;

class poSummaryController extends BaseController
{
    public function POGetData($date)
    {
        $data = DB::connection('sqlsrv_ems2')->select("SELECT * FROM f_frcst_po_mri('" . $date . "', '" . date('Y-m-t', strtotime($date . ' + 3 months')) . "', 0) order by item_code, period_iter");

        // return $data;
        $hasil = [];
        $count = 0;
        $totalm1a = $totalm1b = $totalm2a = $totalm2b = $totalm3 = $totalm4 = 0;
        foreach ($data as $key => $value) {
            if ($key > 0 && $data[$key - 1]->item_code !== $value->item_code) {
                $count++;
                $totalm1a = $totalm1b = $totalm2a = $totalm2b = $totalm3 = $totalm4 = 0;
            }

            if ((int)$value->period_iter === 1) {
                $totalm1a += $value->qty;
            } elseif ((int)$value->period_iter === 2) {
                $totalm1b += $value->qty;
            } elseif ((int)$value->period_iter === 3) {
                $totalm2a += $value->qty;
            } elseif ((int)$value->period_iter === 4) {
                $totalm2b += $value->qty;
            } elseif ((int)$value->period_iter === 5 || $value->period_iter === 6) {
                $totalm3 += $value->qty;
            } elseif ((int)$value->period_iter === 7 || $value->period_iter === 8) {
                $totalm4 += $value->qty;
            }  

            $hasil[$count] = [
                'no' => $count + 1,
                'item_code' => $value->item_code,
                'item_spt' => $value->item_spt,
                'item_desc' => $value->item_desc,
                'item_maker' => $value->item_maker,
                'sup_name' => $value->sup_name,
                'm1a' => $totalm1a,
                'm1b' => $totalm1b,
                'm2a' => $totalm2a,
                'm2b' => $totalm2b,
                'm3' => $totalm3,
                'm4' => $totalm4
            ];
        }

        return $this->handleResponse($hasil, 'Data ditemukan !');
    }

    public function uploadPO(Request $req)
    {
        $nama_file = $req->file->hashName();

        $req->file->storeAs('/public/upload_raw_po/', $nama_file);

        $importer = new importRawPO($req->date);

        Excel::import($importer, public_path('/storage/upload_raw_po/' . $nama_file));

        return $this->handleResponse([], 'Upload Sukses ' . $nama_file);
    }

    public function exportPO($date)
    {
        $data = $this->POGetData($date)->original['data'];

        // return json_encode($data);

        Excel::store(new ExportPOSummary($data, $date), 'export_po_summary.xlsx', 'public');

        return 'storage/app/public/export_po_summary.xlsx';
    }
}
