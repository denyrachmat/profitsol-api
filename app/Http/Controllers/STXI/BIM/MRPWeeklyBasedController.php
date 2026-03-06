<?php

namespace App\Http\Controllers\STXI\BIM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\STXI\BIM\ExportMRPSchemeWeekly;
use Maatwebsite\Excel\Facades\Excel;

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
        /**
         * Creates a date period that iterates through weeks starting from the first Monday.
         * 
         * The period starts at $firstMonday and increments by 1 week (P1W) intervals.
         * It continues until one day after the $end date (modified with '+1 day').
         * 
         * DatePeriod includes the start date but excludes the end date by default.
         * Since the end is modified to '+1 day', the iteration will include dates up to
         * and including the original $end date.
         * 
         * Note: This creates a weekly iterator based on Monday start dates. If the intent
         * is to include full weeks (Monday-Sunday), ensure $firstMonday is correctly set
         * to a Monday and the $end date calculation accounts for the full week range needed.
         * 
         * @var \DatePeriod $period Collection of dates at weekly intervals
         */
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
        // logger($dataHeaders);

        return Excel::download(new ExportMRPSchemeWeekly(
            [
                'mrp_date' => $request->input('mrp_date'),
                'first_date' => $request->input('first_date'),
                'po_rel_date' => $request->input('po_rel_date'),
                'po_iss_date' => $request->input('po_iss_date'),
                'mrp_cutoff_date' => $request->input('mrp_cutoff_date'),
            ],
            $request->input('type_mrp', 'New MRP scheme (weekly base)'),
            49
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
                'po_iss_date' => null,
                'mrp_cutoff_date' => null,
            ]
        ), 'mrp_scheme_weekly.xlsx');
    }
}
