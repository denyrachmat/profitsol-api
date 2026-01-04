<?php

namespace App\Http\Controllers\STXI\BIM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\STXI\BIM\ExportMRPSchemeWeekly;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class MRPWeeklyBasedController extends Controller
{
    public function getData($firstDate, $lastDate, $lt = 0)
    {
        $tz = new \DateTimeZone('Asia/Jakarta');
        $start = new \DateTime($firstDate, $tz);
        $end = new \DateTime($lastDate, $tz);

        // cari Senin pertama pada/ setelah $start
        $firstMonday = (clone $start)->modify('monday this week');
        if ($firstMonday < $start) {
            $firstMonday->modify('+1 week');
        }

        // DatePeriod dengan langkah 1 minggu, inklusif sampai $end
        $period = new \DatePeriod($firstMonday, new \DateInterval('P1W'), (clone $end)->modify('+1 day'));

        $mondays = [];
        foreach ($period as $d) {
            $mondays[] = $d->format('Y-m-d');
        }

        return $mondays;
    }

    public function getReport(Request $request)
    {
        // If this is just checking file info, return headers only without generating the file
        if ($request->input('check_only') || $request->isMethod('head')) {
            return response('', 200)
                ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->header('Content-Disposition', 'attachment; filename="mrp_scheme_weekly.xlsx"')
                ->header('Cache-Control', 'public, max-age=0')
                ->header('Content-Length', '0');
        }

        set_time_limit(300);
        $firstDate = $request->input('first_date');
        $weekCount = $request->input('week_count', 98);

        $lastDate = (new \DateTime($firstDate))->modify('+' . ($weekCount * 7 - 1) . ' days')->format('Y-m-d');
        $lt = $request->input('lt', 21);

        $firstDayOfMonth = (new \DateTime($firstDate))->modify('first day of this month')->format('Y-m-d');
        $dataHeaders = $this->getData($firstDayOfMonth, $lastDate, $lt);

        $getLeadTimeList = DB::connection('sqlsrv_mega_sme')->table('MITM_TBL')->select('MITM_ETALT')->distinct()->where('MITM_ETALT', '>', 0)->get()->toArray();
        $listDataPerLTMega = [];
        foreach ($getLeadTimeList as $key => $valueLT) {
            // logger("CheckLT", ['MITM_ETALT' => $valueLT->MITM_ETALT]);
            $fDateLT = $request->input('mrp_date');
            $lastDatePerLT = (new \DateTime($fDateLT))->modify('+' . ((int) ($valueLT->MITM_ETALT + 49) + 1) . ' days')->format('Y-m-d');
            $getDateData = $this->getData($request->input('mrp_date'), $lastDatePerLT);

            if (!empty($getDateData)) {
                $listDataPerLTMega[(int) $valueLT->MITM_ETALT] = $this->getData($request->input('mrp_date'), $lastDatePerLT);
            }
        }

        // logger($listDataPerLTMega);

        return Excel::download(new ExportMRPSchemeWeekly(
            [
                'headers' => $dataHeaders
            ],
            [
                'mrp_date' => $request->input('mrp_date'),
                'first_date' => $request->input(key: 'first_date'),
                'po_rel_date' => $request->input('po_rel_date'),
                'mrp_cutoff_date' => $request->input('mrp_cutoff_date'),
            ],
            $listDataPerLTMega
        ), 'mrp_scheme_weekly.xlsx');
    }

    public function getReportTest($startDate, $weekCount = 98, $lt = 21)
    {
        $firstDate = $startDate;
        $lastDate = (new \DateTime($firstDate))->modify('+' . ($weekCount * 7 - 1) . ' days')->format('Y-m-d');

        // return [$firstDate, $lastDate, $lt];

        $dataHeaders = $this->getData($firstDate, $lastDate, $lt);

        return Excel::download(new ExportMRPSchemeWeekly(
            [
                'headers' => $dataHeaders
            ],
            [
                'mrp_date' => null,
                'first_date' => $firstDate,
                'po_rel_date' => null,
                'mrp_cutoff_date' => null,
            ]
        ), 'mrp_scheme_weekly.xlsx');
    }
}
