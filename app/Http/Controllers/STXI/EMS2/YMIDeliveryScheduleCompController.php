<?php

namespace App\Http\Controllers\STXI\EMS2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class YMIDeliveryScheduleCompController extends Controller
{
    public function export(Request $request)
    {
        logger('request', $request->all());
        return response()->json(['message' => 'Export functionality is currently disabled.'], 403);
        
        // Validate the request parameters
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // Extract the start and end dates from the request
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Call the service to generate the Excel file
        $excelFile = app('App\Services\YMIDeliveryScheduleService')->exportToExcel($startDate, $endDate);

        // Return the Excel file as a response for download
        return response()->download($excelFile, 'YMI_Delivery_Schedule.xlsx')->deleteFileAfterSend(true);
    }
}
