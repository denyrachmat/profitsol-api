<?php

namespace App\Http\Controllers\STXI\EMS2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            'pattern' => 'nullable|string',
            'patterns' => 'nullable|array',
            'patterns.*' => 'required|string',
        ]);

        // Wildcard filters, e.g. ["*.xlsx", "*delivery*"]. A file is kept if it
        // matches ANY pattern (OR). `pattern` (single) is still accepted. Default
        // ["*"] = all files. Matches against the basename; switch basename($file)
        // to $file below to match the full path.
        $patterns = array_values(array_filter(array_merge(
            (array) $request->input('patterns', []),
            $request->filled('pattern') ? [$request->input('pattern')] : []
        )));
        if (empty($patterns)) {
            $patterns = ['*'];
        }

        // installDisk() already returns a filesystem disk instance, so use it
        // directly. allFiles() recurses into child folders; use files() if you
        // only want the top level.
        $disk = $this->installDisk('ems2_yeid_root');

        $result = [];
        foreach ($request->folder as $valueFolder) {
            $result[$valueFolder] = collect($disk->allFiles($valueFolder))
                ->filter(function ($file) use ($patterns) {
                    $name = basename($file);
                    foreach ($patterns as $pattern) {
                        if (Str::is($pattern, $name)) {
                            return true;
                        }
                    }
                    return false;
                })
                ->values()
                ->all();
        }

        logger()->info('YMIDeliveryScheduleCompController export result: ', $result);
        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }
}
