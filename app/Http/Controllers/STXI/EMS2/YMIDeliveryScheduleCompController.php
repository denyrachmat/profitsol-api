<?php

namespace App\Http\Controllers\STXI\EMS2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Exports\STXI\EMS2\ExportDeliveryScheduleComp;
use App\Traits\DMS\FolderDocumentTraits;
use Excel;

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
            'pattern' => 'nullable',
            'patterns' => 'nullable',
        ]);

        // `patterns` may arrive as a real array, a JSON string or a comma-separated
        // string; `pattern` (single) is still accepted. Default ["*"] = all files.
        $patterns = $this->normalizePatterns(
            $request->input('patterns', []),
            $request->filled('pattern') ? $request->input('pattern') : null
        );
        if (empty($patterns)) {
            $patterns = ['*'];
        }

        // A file is kept if it matches ANY name pattern (OR), AND has one of the
        // requested extensions (also OR). Pure-extension patterns like "*.xlsx"
        // are treated as a file-type filter rather than a name alternative, so
        // ["STX Forecast*", "DS Ex*", "*.xlsx"] means
        // "(STX Forecast* OR DS Ex*) AND .xlsx" — not "every .xlsx".
        [$namePatterns, $extensions] = $this->splitPatterns($patterns);

        // installDisk() already returns a filesystem disk instance, so use it
        // directly.
        $disk = $this->installDisk('ems2_yeid_root');

        $matchedFiles = [];
        foreach ($request->folder as $valueFolder) {
            $visited = [];
            $matchedFiles = array_merge(
                $matchedFiles,
                $this->collectFilesRecursive($disk, $valueFolder, $namePatterns, $extensions, $visited)
            );
        }
        $matchedFiles = array_values(array_unique($matchedFiles));

        // Read every matched spreadsheet into raw rows and hand them to the export
        // class, which is responsible for parsing/formatting.
        $rows = [];
        foreach ($matchedFiles as $file) {
            try {
                $sheets = Excel::toArray(new \stdClass, $disk->path($file));
            } catch (\Throwable $e) {
                logger()->warning('YMIDeliveryScheduleCompController failed reading ' . $file, [
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            foreach ($sheets as $sheetRows) {
                foreach ($sheetRows as $row) {
                    $rows[] = $row;
                }
            }
        }

        logger()->info('YMIDeliveryScheduleCompController export', [
            'files' => $matchedFiles,
            'row_count' => count($rows),
        ]);

        return Excel::download(
            new ExportDeliveryScheduleComp($rows, [
                'inc' => (int) $request->inc,
                'dec' => (int) $request->dec,
                'bg' => $request->bg,
                'folders' => $request->folder,
                'patterns' => $patterns,
                'files' => $matchedFiles,
            ]),
            'DeliveryScheduleComp_' . date('Ymd_His') . '.xlsx'
        );
    }

    /**
     * Recursively collect files under $directory matching the name patterns and
     * extensions.
     *
     * For local disks we read the directory with PHP's scandir()/is_dir() on the
     * resolved path. Flysystem's iterators treat junctions/reparse points (common
     * on mapped NAS drives) as files and never descend into them, so recursive
     * listing stopped after one level. Non-local disks fall back to Flysystem.
     */
    private function collectFilesRecursive($disk, string $directory, array $namePatterns, array $extensions, array &$visited, int $depth = 0): array
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
                        $this->collectFilesRecursive($disk, $rel, $namePatterns, $extensions, $visited, $depth + 1)
                    );
                } elseif ($this->fileMatches($entry, $namePatterns, $extensions)) {
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
            if ($this->fileMatches(basename($file), $namePatterns, $extensions)) {
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
                $this->collectFilesRecursive($disk, $child, $namePatterns, $extensions, $visited, $depth + 1)
            );
        }

        return $matches;
    }

    /**
     * Split wildcard patterns into name patterns (OR) and pure extension
     * patterns (`*.xlsx`, OR among themselves, ANDed with the name group).
     */
    private function splitPatterns(array $patterns): array
    {
        $names = [];
        $extensions = [];

        foreach ($patterns as $pattern) {
            $pattern = trim((string) $pattern);
            if ($pattern === '') {
                continue;
            }

            if (preg_match('/^\*\.([A-Za-z0-9]+)$/', $pattern, $match)) {
                $extensions[] = strtolower($match[1]);
            } else {
                $names[] = $pattern;
            }
        }

        return [array_values(array_unique($names)), array_values(array_unique($extensions))];
    }

    private function fileMatches(string $name, array $namePatterns, array $extensions): bool
    {
        $base = basename($name);

        if (!empty($extensions)) {
            $extension = strtolower(pathinfo($base, PATHINFO_EXTENSION));
            if (!in_array($extension, $extensions, true)) {
                return false;
            }
        }

        if (empty($namePatterns)) {
            return true;
        }

        foreach ($namePatterns as $pattern) {
            if (Str::is($pattern, $base)) {
                return true;
            }
        }

        return false;
    }

    private function normalizePatterns($patterns, ?string $single): array
    {
        if (is_string($patterns)) {
            $decoded = json_decode($patterns, true);
            $patterns = is_array($decoded)
                ? $decoded
                : array_map('trim', explode(',', $patterns));
        }

        $patterns = is_array($patterns) ? $patterns : [];

        if ($single !== null && $single !== '') {
            $patterns[] = $single;
        }

        return array_values(array_filter(array_map(
            fn ($pattern) => is_string($pattern) ? trim($pattern) : $pattern,
            $patterns
        ), fn ($pattern) => $pattern !== null && $pattern !== ''));
    }
}
