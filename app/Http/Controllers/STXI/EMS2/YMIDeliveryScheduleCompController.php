<?php

namespace App\Http\Controllers\STXI\EMS2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Storage;

class YMIDeliveryScheduleCompController extends Controller
{
    public function export(Request $request)
    {        
        // Validate the request parameters
        $request->validate([
            'inc' => 'required|integer',
            'dec' => 'required|integer',
            'bg' => 'required|string',
            'folder' => 'required|array',
        ]);

        $result = [];
        foreach ($request->folder as $keFolder => $valueFolder) {
            $result[] = Storage::disk('ems2_yeid_root')->path($valueFolder)->files();
        }

        logger()->info('YMIDeliveryScheduleCompController export result: ', $result);
        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }
}
