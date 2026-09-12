<?php

namespace App\Http\Controllers\STXI\EMS2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Traits\DMS\FolderDocumentTraits;

class YMIDeliveryScheduleCompController extends Controller
{
    use FolderDocumentTraits;
    public function export(Request $request)
    {        
        // Validate the request parameters
        $request->validate([
            'inc' => 'required|integer',
            'dec' => 'required|integer',
            'bg' => 'required|string',
            'folder' => 'required|array',
            'folder.*' => 'required|string',
        ]);

        // installDisk() already returns a filesystem disk instance, so use it
        // directly. Use allFiles() instead of files() if you also need files in
        // nested sub-folders.
        $disk = $this->installDisk('ems2_yeid_root');

        $result = [];
        foreach ($request->folder as $valueFolder) {
            $result[$valueFolder] = $disk->files($valueFolder);
        }

        logger()->info('YMIDeliveryScheduleCompController export result: ', $result);
        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }
}
