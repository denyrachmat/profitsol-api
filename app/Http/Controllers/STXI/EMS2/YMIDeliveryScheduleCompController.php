<?php

namespace App\Http\Controllers\STXI\EMS2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Exports\STXI\EMS2\ExportDeliveryScheduleComp;
use App\Traits\DMS\FolderDocumentTraits;
use Excel;
use DB;

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

        // Read every matched spreadsheet into raw rows, separated by source
        // type and file:
        //   $rows[<relative path>]   = rows of "DS Ex*" files
        //   $listFC[<relative path>] = rows of "*Forecast*" files
        $rows = [];
        $listFC = [];

        $getItem = DB::connection('sqlsrv_mega_exim')->table('MITM_TBL')
            ->select(
                'MITM_ITMCD',
                DB::raw("RTRIM(REPLACE(MITM_ITMCD, '-', '')) AS MITM_ITMCD_YEID"),
                'MITM_ITMD1',
                'MITM_SPTNO',
                'MITM_MAKERNM',
                'MSUP_SUPNM',
                'MITM_PLTDAY'
            )
            ->join('MSUP_TBL', 'MITM_TBL.MITM_SUPCD', '=', 'MSUP_TBL.MSUP_SUPCD')
            ->where('MITM_SECCD', 'EXIM-MRC')
            ->get()
            ->toArray();

        foreach ($matchedFiles as $file) {
            $fileName = basename($file);
            $isForecast = stripos($fileName, 'forecast') !== false; // "STX Forecast*"
            $isDS = stripos($fileName, 'ds ex') !== false;          // "DS Ex*" / "DS Excel*"

            // Only Forecast and DS Excel files are consumed; ignore anything else.
            if (!$isForecast && !$isDS) {
                logger()->warning('YMIDeliveryScheduleCompController skipped non DS/Forecast file ' . $file);
                continue;
            }

            try {
                $sheets = app(\App\Services\SpreadsheetReaderService::class)->toArray($disk->path($file));
            } catch (\Throwable $e) {
                logger()->warning('YMIDeliveryScheduleCompController failed reading ' . $file, [
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            // Prefer the date encoded in the folder/file name; fall back to the
            // file's last-modified date so the report never shows a blank MRP
            // Date.
            $mrpDate = $this->resolveDateFromPath($file);
            if ($mrpDate === null) {
                try {
                    $mtime = @filemtime($disk->path($file));
                    if ($mtime !== false) {
                        $mrpDate = date('Y-m-d', $mtime);
                    }
                } catch (\Throwable $e) {
                    // Leave it null if the file time cannot be read.
                }
            }

            // Build a row keyed by the master item matching the raw cell value.
            $buildRow = function (string $itemCode, ?float $qty, ?string $indicatedDate, array $fcMonths) use ($getItem, $mrpDate) {
                $checkItem = array_filter($getItem, function ($item) use ($itemCode) {
                    return $item->MITM_ITMCD_YEID === $itemCode;
                });

                $dataItem = !empty($checkItem) ? array_values($checkItem)[0] : null;

                return [
                    'item_stxi' => $dataItem->MITM_ITMCD ?? null,
                    'item_yeid' => $dataItem->MITM_ITMCD_YEID ?? null,
                    'maker_pn' => $dataItem->MITM_SPTNO ?? null,
                    'description' => $dataItem->MITM_ITMD1 ?? null,
                    'maker_name' => $dataItem->MITM_MAKERNM ?? null,
                    'supplier_name' => $dataItem->MSUP_SUPNM ?? null,
                    'lt' => $dataItem->MITM_PLTDAY ?? null,
                    'mrp_date' => $mrpDate,
                    'qty' => $qty,
                    'indicated_date' => $indicatedDate,
                    'fc_months' => $fcMonths,
                ];
            };

            if ($isForecast) {
                $fileRows = [];
                foreach ($this->extractForecastRows($sheets) as $parsed) {
                    $fileRows[] = $buildRow($parsed['item'], null, null, $parsed['fc_months']);
                }
                if (!empty($fileRows)) {
                    $listFC[$file] = $fileRows;
                }
            } elseif ($isDS) {
                $fileRowsDS = [];
                foreach ($this->extractDsRows($sheets) as $parsed) {
                    $fileRowsDS[] = $buildRow($parsed['item'], $parsed['qty'], $parsed['indicated_date'], []);
                }
                if (!empty($fileRowsDS)) {
                    $rows[$file] = $fileRowsDS;
                }
            }
        }


        // A period folder often holds several Forecast files (originals plus
        // revisions such as "(NEW) ..._20250612.xlsx"). Summing them all
        // double-counts, so merge per MRP date: the latest-dated file wins on
        // overlapping items, and items seen only in older files are kept.
        $listFC = $this->mergeForecastPerMrpDate($listFC);

        logger()->info('YMIDeliveryScheduleCompController export', [
            'FC' => $listFC,
            'DS' => $rows,
        ]);

        // logger()->info('YMIDeliveryScheduleCompController export', [
        //     'files' => $matchedFiles,
        //     'row_count' => array_sum(array_map('count', $rows)),
        //     'row_count_per_file' => array_map('count', $rows),
        // ]);

        // Drop unmatched rows (blank item code) per file, then drop files left
        // empty. Filtering the outer array by 'item_stxi' would test a whole
        // file's row-list against a single key and remove every file.
        $rows = $this->keepMatchedItemRows($rows);
        $listFC = $this->keepMatchedItemRows($listFC);

        return Excel::download(
            new ExportDeliveryScheduleComp($listFC, [
                'inc' => (int) $request->inc,
                'dec' => (int) $request->dec,
                'bg' => $request->bg,
                'folders' => $request->folder,
                'patterns' => $patterns,
                'files' => $matchedFiles,
            ], $rows),
            'DeliveryScheduleComp_' . date('Ymd_His') . '.xlsx'
        );
    }

    /**
     * Extract DS ("DS Ex*") rows. The item code and quantity columns are located
     * by header name rather than fixed offsets, because the legacy exports place
     * "ItemNumber" / "RemainQty" / "IndicatedDate" at different indexes.
     *
     * @return array<int, array{item: string, qty: ?float, indicated_date: ?string}>
     */
    private function extractDsRows(array $sheets): array
    {
        $out = [];

        foreach ($sheets as $grid) {
            if (empty($grid)) {
                continue;
            }

            // The header is the first row containing an "ItemNumber" cell.
            $headerRow = null;
            $map = [];
            foreach ($grid as $r => $row) {
                $candidate = [];
                foreach ($row as $c => $v) {
                    $name = strtolower(trim((string) $v));
                    if ($name !== '') {
                        $candidate[$name] = $c;
                    }
                }
                if (isset($candidate['itemnumber'])) {
                    $headerRow = $r;
                    $map = $candidate;
                    break;
                }
            }

            if ($headerRow === null || !isset($map['itemnumber'])) {
                continue;
            }

            $itemCol = $map['itemnumber'];
            $qtyCol = $map['remainqty'] ?? $map['indicatedqty'] ?? null;
            $dateCol = $map['indicateddate'] ?? null;

            foreach ($grid as $r => $row) {
                if ($r <= $headerRow) {
                    continue;
                }

                $item = $row[$itemCol] ?? null;
                $item = is_string($item) ? trim($item) : $item;
                if ($item === null || $item === '' || $item === ' ') {
                    continue;
                }

                $out[] = [
                    'item' => (string) $item,
                    'qty' => $qtyCol !== null ? $this->toNumber($row[$qtyCol] ?? null) : null,
                    'indicated_date' => $dateCol !== null ? $this->normalizeDate($row[$dateCol] ?? null) : null,
                ];
            }
        }

        return $out;
    }

    /**
     * Extract Forecast rows from the "Latest Forecast Order to STX" table — the
     * first "Forecast Order to STX" month group (sheet 1 when present, otherwise
     * the first 6 YYYYMM columns of sheet 0).
     *
     * @return array<int, array{item: string, fc_months: array<string, float>}>
     */
    private function extractForecastRows(array $sheets): array
    {
        $chosen = null;
        foreach ($sheets as $grid) {
            $info = $this->forecastMonthInfo($grid);
            if ($info === null) {
                continue;
            }
            if (!empty($info['latest'])) {
                $chosen = $info;
                break;
            }
            if ($chosen === null) {
                $chosen = $info;
            }
        }

        if ($chosen === null || $chosen['itemCol'] === null) {
            return [];
        }

        $out = [];
        foreach ($chosen['grid'] as $r => $row) {
            if ($r <= $chosen['monthRow']) {
                continue;
            }

            $item = $row[$chosen['itemCol']] ?? null;
            $item = is_string($item) ? trim($item) : $item;
            if ($item === null || $item === '' || $item === ' ') {
                continue;
            }

            $months = [];
            foreach ($chosen['months'] as $col => $month) {
                $qty = $this->toNumber($row[$col] ?? null) ?? 0.0;
                $months[$month] = ($months[$month] ?? 0) + $qty;
            }

            $out[] = ['item' => (string) $item, 'fc_months' => $months];
        }

        return $out;
    }

    /**
     * Locate the month header of a Forecast sheet.
     *
     * Returns the header row (the row with the most YYYYMM cells), the item-code
     * column ("Itemno"), and the month columns belonging to the first
     * "Forecast Order to STX" group (the one immediately left of the next
     * sub-header label, e.g. "Reference : Requirement YEID").
     *
     * @return array{grid: array, monthRow: int, months: array<int, string>, itemCol: ?int, latest: bool}|null
     */
    private function forecastMonthInfo(array $grid): ?array
    {
        $monthRow = null;
        $bestMonths = [];
        foreach ($grid as $r => $row) {
            $months = [];
            foreach ($row as $c => $v) {
                $s = trim((string) $v);
                if ($this->isYearMonth($s)) {
                    $months[$c] = $s;
                }
            }
            if (count($months) > count($bestMonths)) {
                $bestMonths = $months;
                $monthRow = $r;
            }
        }

        if ($monthRow === null || empty($bestMonths)) {
            return null;
        }

        $itemCol = null;
        foreach ($grid[$monthRow] as $c => $v) {
            if (strtolower(trim((string) $v)) === 'itemno') {
                $itemCol = $c;
                break;
            }
        }

        // Sub-header labels sit directly above the month row.
        $labels = [];
        if ($monthRow > 0) {
            foreach ($grid[$monthRow - 1] as $c => $v) {
                $s = strtolower(trim((string) $v));
                if ($s !== '') {
                    $labels[$c] = $s;
                }
            }
            ksort($labels);
        }

        $start = null;
        foreach ($labels as $c => $label) {
            if (strpos($label, 'forecast order to stx') !== false) {
                $start = $c;
                break;
            }
        }

        $latest = false;
        $months = $bestMonths;
        if ($start !== null) {
            $latest = strpos($labels[$start], 'latest') !== false;
            $end = null;
            foreach ($labels as $c => $label) {
                if ($c > $start) {
                    $end = $c;
                    break;
                }
            }
            $months = array_filter(
                $bestMonths,
                fn ($c) => $c >= $start && ($end === null || $c < $end),
                ARRAY_FILTER_USE_KEY
            );
        } else {
            // No sub-header labels: a single month table, keep the first 6 months.
            $months = array_slice($bestMonths, 0, 6, true);
        }

        if (empty($months)) {
            return null;
        }

        return [
            'grid' => $grid,
            'monthRow' => $monthRow,
            'months' => $months,
            'itemCol' => $itemCol,
            'latest' => $latest,
        ];
    }

    /**
     * True for a YYYYMM token within a plausible year/month range (2000-2099).
     */
    private function isYearMonth(string $value): bool
    {
        return preg_match('/^20\d{2}(0[1-9]|1[0-2])$/', $value) === 1;
    }

    /**
     * Coerce a cell into a float, or null when it is not numeric.
     */
    private function toNumber($value): ?float
    {
        if ($value === null || $value === '' || $value === ' ') {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $s = str_replace(',', '', trim((string) $value));

        return $s !== '' && is_numeric($s) ? (float) $s : null;
    }

    /**
     * Normalise a date cell into Y-m-d. Handles YYYYMMDD strings/integers and
     * Excel serial dates.
     */
    private function normalizeDate($value): ?string
    {
        if ($value === null || $value === '' || $value === ' ') {
            return null;
        }

        $s = trim((string) $value);
        if (preg_match('/^\d{8}$/', $s)) {
            return substr($s, 0, 4) . '-' . substr($s, 4, 2) . '-' . substr($s, 6, 2);
        }

        if (is_numeric($s)) {
            $serial = (float) $s;
            if ($serial > 1 && $serial < 2958466) {
                return gmdate('Y-m-d', (int) (($serial - 25569) * 86400));
            }
        }

        $timestamp = strtotime($s);

        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
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
            fn($pattern) => is_string($pattern) ? trim($pattern) : $pattern,
            $patterns
        ), fn($pattern) => $pattern !== null && $pattern !== ''));
    }

    /**
     * Merge Forecast files that share an MRP date so quantities are not summed
     * twice. Within each MRP date the file with the latest filename date takes
     * precedence on overlapping items, and items that only appear in an older
     * file are still kept.
     *
     * @param array<string, array<int, array>> $listFC file path => rows
     * @return array<string, array<int, array>>
     */
    private function mergeForecastPerMrpDate(array $listFC): array
    {
        $byDate = [];

        foreach ($listFC as $file => $fileRows) {
            if (empty($fileRows)) {
                continue;
            }

            $mrpDate = $fileRows[0]['mrp_date'] ?? null;
            $groupKey = $mrpDate ?? ('file:' . $file);

            $byDate[$groupKey][] = [
                'file' => $file,
                'date' => $this->dateFromFilename($file) ?? ($mrpDate ?? ''),
                'rows' => $fileRows,
            ];
        }

        $merged = [];

        foreach ($byDate as $groupKey => $files) {
            if (count($files) === 1) {
                $merged[$files[0]['file']] = $files[0]['rows'];
                continue;
            }

            // Newest filename date first, so it wins on overlapping items.
            usort($files, fn ($a, $b) => strcmp($b['date'], $a['date']));

            $seen = [];
            $rows = [];
            foreach ($files as $entry) {
                foreach ($entry['rows'] as $row) {
                    $itemKey = (string) ($row['item_stxi'] ?? $row['item_yeid'] ?? '');
                    if ($itemKey === '' || isset($seen[$itemKey])) {
                        continue;
                    }

                    $seen[$itemKey] = true;
                    $rows[] = $row;
                }
            }

            $merged['merged:' . $groupKey] = $rows;
        }

        return $merged;
    }

    /**
     * The YYYYMMDD date encoded in a filename as Y-m-d, or null.
     */
    private function dateFromFilename(string $file): ?string
    {
        if (preg_match('/(\d{4})(\d{2})(\d{2})/', basename($file), $m)) {
            return sprintf('%s-%s-%s', $m[1], $m[2], $m[3]);
        }

        return null;
    }

    /**
     * Keep only rows whose item code matched a master item, per file, then drop
     * files that end up with no rows.
     *
     * @param array<string, array<int, array>> $grouped file path => rows
     * @return array<int, array<int, array>>
     */
    private function keepMatchedItemRows(array $grouped): array
    {
        $kept = array_map(function ($fileRows) {
            return array_values(array_filter($fileRows, function ($row) {
                return !empty($row['item_stxi']);
            }));
        }, $grouped);

        return array_values(array_filter($kept, function ($fileRows) {
            return !empty($fileRows);
        }));
    }

    /**
     * Resolve a YYYY-MM-DD date from a file path.
     *
     * Scans for a "date folder" like "01. 09 JAN" anywhere in the path and
     * pairs it with the nearest 4-digit year folder (usually its parent), so
     * extra folders between the date and the file are handled, e.g.
     * ".../2026/01. 09 JAN/YEID DATA/DS EXCEL.xls". Falls back to a YYYYMMDD
     * token in the filename (e.g. "STX Forecast data_20260109.xlsx").
     */
    private function resolveDateFromPath(string $path): ?string
    {
        $segments = array_values(array_filter(
            explode('/', str_replace('\\', '/', $path)),
            fn($s) => $s !== ''
        ));

        foreach ($segments as $index => $segment) {
            // "01. 09 JAN", "09 JAN", "11. 09 JUN 2025" or "09 JUN 25" —
            // optional sequence prefix, day, month name and an optional inline
            // (2- or 4-digit) year.
            if (!preg_match('/^(?:\d{1,2}\.\s*)?(\d{1,2})\s+([A-Za-z]{3,})(?:\s+(\d{2}|\d{4}))?$/', trim($segment), $m)) {
                continue;
            }

            // Prefer a year in the folder name, otherwise the nearest 4-digit
            // year segment above it.
            $year = !empty($m[3]) ? $this->expandYear((int) $m[3]) : $this->findYearSegment($segments, $index);
            if ($year === null) {
                continue;
            }

            $date = $this->makeDate((int) $m[1], $m[2], $year);
            if ($date !== null) {
                return $date;
            }
        }

        // Loose pass: a day + month embedded in a date-like folder such as
        // "MRP 06MAY", "13. YEID NEW PO 08JUL" or "11. YEID NEW PO 10 JUNI 2024".
        // Requires a day immediately followed by a month name (optionally with a
        // year), so "01. PURCHASING", "FROM CUSTOMER" or "PO PER MONTH" do not
        // match.
        foreach ($segments as $index => $segment) {
            if (!preg_match('/^(?:.*\b)?(\d{1,2})\s*([A-Za-z]{3,})(?:\s+(\d{2}|\d{4}))?$/', trim($segment), $m)) {
                continue;
            }

            $year = !empty($m[3]) ? $this->expandYear((int) $m[3]) : $this->findYearSegment($segments, $index);
            if ($year === null) {
                continue;
            }

            $date = $this->makeDate((int) $m[1], $m[2], $year);
            if ($date !== null) {
                return $date;
            }
        }

        // Dotted day.month.year folder, e.g. "23.01.18", "05.04.2019" or
        // "01. 05.04.19" (leading sequence number).
        foreach ($segments as $segment) {
            if (!preg_match('/^(?:\d{1,2}\.\s*)?(\d{1,2})\.(\d{1,2})\.(\d{2}|\d{4})$/', trim($segment), $m)) {
                continue;
            }

            $day = (int) $m[1];
            $month = (int) $m[2];
            $year = strlen($m[3]) === 2 ? $this->expandYear((int) $m[3]) : (int) $m[3];

            $date = \DateTimeImmutable::createFromFormat('j n Y', sprintf('%d %d %d', $day, $month, $year));
            if (
                $date !== false
                && (int) $date->format('j') === $day
                && (int) $date->format('n') === $month
                && (int) $date->format('Y') === $year
            ) {
                return $date->format('Y-m-d');
            }
        }

        // Six-digit DDMMYY folder, e.g. "091017" (optionally with a leading
        // sequence number). Months outside 01-12 are rejected, so YYYYMM tokens
        // ("202506") are ignored.
        foreach ($segments as $segment) {
            if (!preg_match('/^(?:\d{1,2}\.\s*)?(\d{2})(\d{2})(\d{2})$/', trim($segment), $m)) {
                continue;
            }

            $day = (int) $m[1];
            $month = (int) $m[2];
            if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
                continue;
            }

            return sprintf('%04d-%02d-%02d', $this->expandYear((int) $m[3]), $month, $day);
        }

        $filename = $segments[count($segments) - 1] ?? '';

        // Fallback: a YYYYMMDD token in the filename.
        if (preg_match('/(\d{4})(\d{2})(\d{2})/', $filename, $m)) {
            return sprintf('%s-%s-%s', $m[1], $m[2], $m[3]);
        }

        // Fallback: a YYMMDD token in the filename, e.g. "... data 171009.xlsx".
        if (preg_match('/(?<!\d)(\d{2})(\d{2})(\d{2})(?!\d)/', $filename, $m)) {
            $year = $this->expandYear((int) $m[1]);
            $month = (int) $m[2];
            $day = (int) $m[3];
            if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        return null;
    }

    /**
     * Build a Y-m-d from a day, a month name (full name or at least its first
     * three letters) and a year, or null when the combination is not a real
     * date, e.g. month "YEID" from "... 08 JUL" when the year token was
     * swallowed.
     */
    private function makeDate(int $day, string $month, int $year): ?string
    {
        $monthNumber = $this->monthNumber($month);
        if ($monthNumber === null || $day < 1 || $day > 31) {
            return null;
        }

        if (!checkdate($monthNumber, $day, $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $monthNumber, $day);
    }

    /**
     * Indonesian (JUNI/JULI/AGUSTUS/...) and English month names, tolerant of
     * plurals/abbreviations.
     */
    private function monthNumber(string $month): ?int
    {
        $map = [
            'jan' => 1, 'januari' => 1, 'january' => 1,
            'feb' => 2, 'februari' => 2, 'february' => 2, 'februaries' => 2,
            'mar' => 3, 'maret' => 3, 'march' => 3,
            'apr' => 4, 'april' => 4,
            'mei' => 5, 'may' => 5,
            'jun' => 6, 'juni' => 6, 'june' => 6,
            'jul' => 7, 'juli' => 7, 'july' => 7,
            'agu' => 8, 'agustus' => 8, 'aug' => 8, 'august' => 8,
            'sep' => 9, 'sept' => 9, 'september' => 9,
            'okt' => 10, 'oktober' => 10, 'oct' => 10, 'october' => 10,
            'nov' => 11, 'november' => 11,
            'des' => 12, 'desember' => 12, 'dec' => 12, 'december' => 12,
        ];

        $key = strtolower(trim($month));

        return $map[$key] ?? ($map[substr($key, 0, 3)] ?? null);
    }

    /**
     * Expand a two-digit year into a full year (00-69 -> 2000s, 70-99 -> 1900s).
     */
    private function expandYear(int $year): int
    {
        if ($year >= 100) {
            return $year;
        }

        return $year < 70 ? 2000 + $year : 1900 + $year;
    }

    /**
     * Nearest 4-digit year segment at or before the given index.
     */
    private function findYearSegment(array $segments, int $index): ?int
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            if (preg_match('/^\d{4}$/', trim($segments[$i]))) {
                return (int) $segments[$i];
            }
        }

        return null;
    }
}
