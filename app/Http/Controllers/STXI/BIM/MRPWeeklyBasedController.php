<?php

namespace App\Http\Controllers\STXI\BIM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MRPWeeklyBasedController extends Controller
{
    public function getData($firstDate, $lastDate, $lt = 21)
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
            $mondays['headers'][] = $d->format('Y-m-d');
        }

        return $mondays;
    }
}
