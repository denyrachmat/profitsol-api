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
        // directly.
        $disk = $this->installDisk('ems2_yeid_root');

        $result = [];
        foreach ($request->folder as $valueFolder) {
            $visited = [];
            $result[$valueFolder] = $this->collectFilesRecursive($disk, $valueFolder, $patterns, $visited);
        }

        logger()->info('YMIDeliveryScheduleCompController export result: ', $result);
        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    /**
     * Recursively collect files under $directory whose basename matches any of
     * the wildcard $patterns.
     *
     * For local disks we read the directory with PHP's scandir()/is_dir() on the
     * resolved path. Flysystem's iterators treat junctions/reparse points (common
     * on mapped NAS drives) as files and never descend into them, so recursive
     * listing stopped after one level. Non-local disks fall back to Flysystem.
     */
    private function collectFilesRecursive($disk, string $directory, array $patterns, array &$visited, int $depth = 0): array
    {
        if ($depth > 30) {
            return [];
        }

        $directory = trim($directory, '/');
        if (isset($visited[$directory])) {
            return [];
        }
        $visited[$directory] = true;

        $matches = [];

        try {
            $absDirectory = $disk->path($directory);
        } catch (\Throwable $e) {
            $absDirectory = null;
        }

        if ($absDirectory !== null && is_dir($absDirectory)) {
            $entries = @scandir($absDirectory) ?: [];
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $abs = rtrim($absDirectory, '\\/') . DIRECTORY_SEPARATOR . $entry;
                $rel = ($directory === '' ? '' : $directory . '/') . $entry;

                if (is_dir($abs)) {
                    $matches = array_merge(
                        $matches,
                        $this->collectFilesRecursive($disk, $rel, $patterns, $visited, $depth + 1)
                    );
                } elseif ($this->matchesAnyPattern($entry, $patterns)) {
                    $matches[] = $rel;
                }
            }

            return $matches;
        }

        // Fallback for non-local disks where path() is unavailable.
        try {
            $files = $disk->files($directory);
        } catch (\Throwable $e) {
            $files = [];
        }
        foreach ($files as $file) {
            if ($this->matchesAnyPattern(basename($file), $patterns)) {
                $matches[] = $file;
            }
        }

        try {
            $children = $disk->directories($directory);
        } catch (\Throwable $e) {
            $children = [];
        }
        foreach ($children as $child) {
            $matches = array_merge(
                $matches,
                $this->collectFilesRecursive($disk, $child, $patterns, $visited, $depth + 1)
            );
        }

        return $matches;
    }

    private function matchesAnyPattern(string $name, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $name)) {
                return true;
            }
        }

        return false;
    }
}
