<?php

namespace App\Http\Controllers\STXI\EMS2;

use Illuminate\Http\Request;
use App\Imports\STXI\importRawPO;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\API\PORTAL\BaseController;
use App\Models\STXI\EMS2\FRCST_PO_MRI;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\File;
use App\Exports\STXI\ExportPOSummary;
use App\Exports\STXI\ExportPODetSummary;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class poSummaryController extends BaseController
{
    public function POGetData($date)
    {
        $data = DB::connection('sqlsrv_ems2')->select("SELECT * FROM f_frcst_po_mri('" . $date . "', '" . date('Y-m-t', strtotime($date . ' + 3 months')) . "', 0, 0) order by item_code, period_iter");

        // return $data;
        $hasil = [];
        $cek = [];
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
                $cek[] = $value;
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
                'm4' => $totalm4,
                'test' => $cek
            ];
        }

        return $this->handleResponse($hasil, 'Data ditemukan !');
    }

    public function POGetDataDet($date)
    {
        $data = FRCST_PO_MRI::select(
            'FPM_ITMCD',
            'MITM_ITMD1',
            'MITM_MAKERNM',
            'MITM_SPTNO',
            'MSUP_SUPNM',
            'FPM_UPLDT',
            DB::raw('SUM(FPM_QTY) AS FPM_QTY')
        )
            ->join(DB::raw('MGSVR.VMI_EXIM.dbo.MITM_TBL'), 'MITM_ITMCD', 'FPM_ITMCD')
            ->join(DB::raw('MGSVR.VMI_EXIM.dbo.MSUP_TBL'), 'MITM_SUPCD', 'MSUP_SUPCD')
            ->whereBetween('FPM_UPLDT', [$date, date("Y-m-t", strtotime($date))])
            ->groupBy(
                'FPM_ITMCD',
                'MITM_ITMD1',
                'MITM_MAKERNM',
                'MITM_SPTNO',
                'MSUP_SUPNM',
                'FPM_UPLDT'
            )
            ->get()
            ->toArray();

        $aDates = array();
        $oStart = new \DateTime($date);
        $oEnd = clone $oStart;
        $oEnd->add(new \DateInterval("P1M"));

        while ($oStart->getTimestamp() < $oEnd->getTimestamp()) {
            $aDates[] = $oStart->format('Y-m-d');
            $oStart->add(new \DateInterval("P1D"));
        }

        $dataDate = [];
        foreach ($aDates as $key => $valueTest) {
            $dataDate[$valueTest] = 0;
        }

        $hasilTemp = [];
        foreach ($data as $key => $value) {
            $hasilTemp[$value['FPM_ITMCD']]['item_code'] = $value['FPM_ITMCD'];
            $hasilTemp[$value['FPM_ITMCD']]['item_desc'] = $value['MITM_ITMD1'];
            $hasilTemp[$value['FPM_ITMCD']]['item_maker'] = $value['MITM_MAKERNM'];
            $hasilTemp[$value['FPM_ITMCD']]['item_spt'] = $value['MITM_SPTNO'];
            $hasilTemp[$value['FPM_ITMCD']]['sup_name'] = $value['MSUP_SUPNM'];
            $hasilTemp[$value['FPM_ITMCD']][$value['FPM_UPLDT']] = $value['FPM_QTY'];
        }

        $hasil = [];

        $keys = 0;
        foreach ($hasilTemp as $keyTot => $valueTot) {
            $cols = [];
            foreach ($aDates as $keyDateDet => $valueDateDet) {
                $cols[$valueDateDet] = isset($valueTot[$valueDateDet]) ? $valueTot[$valueDateDet] : 0;
            }

            $hasil[$keys] = array_merge(['no' => $keys + 1], $valueTot, $cols);

            $keys++;
        }

        return $this->handleResponse($hasil, 'Data ditemukan !');
    }

    public function uploadPO(Request $req)
    {
        ini_set('max_execution_time', '300');
        // $nama_file = $req->file->hashName();
        $file = new File($req->file);
        $extNya = $req->file('file')->getClientOriginalExtension();

        $fileHash = str_replace('.' . $file->extension(), '', $file->hashName());
        $nama_file = $fileHash . '.' . $extNya;

        // return $nama_file;

        $req->file->storeAs('/public/upload_raw_po/', $nama_file);

        if ($extNya == 'xls') {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $writer = new Xlsx($spreadsheet);
            $nama_file = $fileHash.'.xlsx';
            $writer->save('/public/upload_raw_po/'.$nama_file);
        }

        FRCST_PO_MRI::where(DB::raw('MONTH(FPM_UPLDT)'), date('m', strtotime($req->date)))->where(DB::raw('YEAR(FPM_UPLDT)'), date('Y', strtotime($req->date)))->delete();

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

    public function exportPODet($date)
    {
        $data = $this->POGetDataDet($date)->original['data'];

        // return json_encode($data);

        Excel::store(new ExportPODetSummary($data, $date), 'export_po_summary_det.xlsx', 'public');

        return 'storage/app/public/export_po_summary_det.xlsx';
    }
}
