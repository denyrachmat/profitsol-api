<?php

namespace App\Exports\STXI\EMS2;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ExportDeliveryScheduleComp implements FromCollection, WithHeadings, WithEvents, WithStrictNullComparison
{
    /**
     * Fixed columns written before the dynamic MRP bucket columns.
     */
    protected const BASE_HEADINGS = [
        'Item Code STXI',
        'Item Code YEID',
        'Maker P/N',
        'Description',
        'Maker Name',
        'Supplier Name',
        'L/T (days)',
        'MRP Date',
    ];

    /**
     * Request context: inc, dec, bg, folders, patterns, files.
     *
     * @var array
     */
    protected $context;

    /**
     * Rows read from the "Forecast" files (grouped per file).
     *
     * @var array
     */
    protected $listFC;

    /**
     * Rows read from the "Delivery Schedule" files (grouped per file).
     *
     * @var array
     */
    protected $listDS;

    /**
     * Cached MRP bucket dates (derived from the rows).
     *
     * @var string[]|null
     */
    protected $bucketsCache = null;

    /**
     * Item keys (one row per item) in the order they first appear, aligned with
     * the data rows. Populated by collection().
     *
     * @var string[]
     */
    protected $dataRows = [];

    /**
     * Source of each non-empty bucket cell ("<item>|<bucket>") as "fc", "ds" or
     * "both". Used to decide which cells carry the inc/dec font colour.
     *
     * @var array<string, string>
     */
    protected $cellSources = [];

    /**
     * Background band colour per bucket cell ("<item>|<bucket>"): "green" for
     * the DS range, "red" for the FC range, "brown" where they overlap.
     *
     * @var array<string, string>
     */
    protected $cellBg = [];

    /**
     * Font colour per bucket cell ("<item>|<bucket>"): "red" or "indigo".
     *
     * @var array<string, string>
     */
    protected $cellFonts = [];

    /**
     * Rise threshold (%) that turns FC qty indigo.
     *
     * @var float
     */
    protected $inc = 0;

    /**
     * Drop threshold (%) that turns FC qty red.
     *
     * @var float
     */
    protected $dec = 0;

    public function __construct(array $listFC = [], array $context = [], array $listDS = [])
    {
        $this->context = $context;
        $this->listFC = $listFC;
        $this->listDS = $listDS;
        $this->inc = (float) ($context['inc'] ?? 0);
        $this->dec = (float) ($context['dec'] ?? 0);
    }

    /**
     * Header rows. The last row is the fixed columns followed by one column per
     * semi-monthly bucket, labelled as a date (e.g. "1 Jan 2026").
     */
    public function headings(): array
    {
        $bucketLabels = array_map(
            fn (string $date) => date('j M Y', strtotime($date)),
            $this->buckets()
        );

        return [
            [
                'Delivery Schedule Comparison',
            ],
            [
                'Download Date: ' . now()->format('Y-m-d H:i:s'),
            ],
            array_merge(self::BASE_HEADINGS, $bucketLabels),
        ];
    }

    /**
     * Recursively merge every nested "file" group into one flat list of rows.
     *
     * Handles arbitrary nesting, e.g.
     *   [ 'file' => [ '<path>' => [ [row], [row] ] ] ]
     *   [ '<path>' => [ [row], [row] ] ]
     *   [ [row], [row] ]
     *
     * A node is treated as a single row when none of its children are arrays
     * (assoc column row or list of cells); otherwise it is a group and is
     * recursed into. Group keys (file/path) are discarded so all files merge.
     */
    protected function flattenRows(array $node): array
    {
        if (empty($node)) {
            return [];
        }

        $isGroup = true;
        foreach ($node as $child) {
            if (!is_array($child)) {
                $isGroup = false;
                break;
            }
        }

        if (!$isGroup) {
            return [$node];
        }

        $flattened = [];
        foreach ($node as $child) {
            $flattened = array_merge($flattened, $this->flattenRows($child));
        }

        return $flattened;
    }

    /**
     * Semi-monthly MRP buckets — the 1st and 16th of every month.
     *
     * The first bucket is the earliest cutoff on/after $from and the last is
     * the earliest cutoff on/after $to. Example with $from=2026-01-09 and
     * $to=2026-02-20:
     *   2026-01-16, 2026-02-01, 2026-02-16, 2026-03-01
     *
     * @return string[] bucket dates as Y-m-d, ascending
     */
    public function buildMrpBuckets(string $from, string $to): array
    {
        $cursor = $this->firstCutoffOnOrAfter(new \DateTimeImmutable($from));
        $limit = $this->firstCutoffOnOrAfter(new \DateTimeImmutable($to));

        $buckets = [];
        while ($cursor <= $limit) {
            $buckets[] = $cursor->format('Y-m-d');
            $cursor = $this->nextCutoff($cursor);
        }

        return $buckets;
    }

    /**
     * The bucket an MRP date belongs to: the smallest bucket on/after the date.
     * Returns the bucket date (Y-m-d), or null when the date is past the last
     * bucket.
     */
    public function resolveMrpBucket(string $mrpDate, array $buckets): ?string
    {
        $date = (new \DateTimeImmutable($mrpDate))->format('Y-m-d');
        foreach ($buckets as $bucket) {
            if ($date <= $bucket) {
                return $bucket;
            }
        }

        return null;
    }

    /**
     * 0-based column offset of a bucket within the generated bucket list, for
     * placing a value into the matching bucket column. Null when out of range.
     */
    public function mrpBucketIndex(string $mrpDate, array $buckets): ?int
    {
        $bucket = $this->resolveMrpBucket($mrpDate, $buckets);

        return $bucket === null ? null : array_search($bucket, $buckets, true);
    }

    /**
     * Earliest semi-monthly cutoff (1st or 16th) on/after the given date.
     */
    private function firstCutoffOnOrAfter(\DateTimeImmutable $date): \DateTimeImmutable
    {
        $day = (int) $date->format('j');
        if ($day <= 1) {
            return $date->setDate((int) $date->format('Y'), (int) $date->format('n'), 1);
        }
        if ($day <= 16) {
            return $date->setDate((int) $date->format('Y'), (int) $date->format('n'), 16);
        }

        return $date->modify('first day of next month');
    }

    /**
     * The cutoff immediately after the given cutoff (1st -> 16th, 16th -> 1st).
     */
    private function nextCutoff(\DateTimeImmutable $cutoff): \DateTimeImmutable
    {
        $day = (int) $cutoff->format('j');
        if ($day === 1) {
            return $cutoff->setDate((int) $cutoff->format('Y'), (int) $cutoff->format('n'), 16);
        }

        return $cutoff->modify('first day of next month');
    }

    /**
     * Semi-monthly bucket dates (Y-m-d, 1st/16th) for the whole export,
     * ascending. The range starts from the files' MRP date and extends over the
     * Forecast "Latest FC" months (each anchored to the 1st), so every MRP date
     * has a column and every forecast month gets a "1 <Mon>" column.
     *
     * @return string[] Y-m-d ascending
     */
    protected function buckets(): array
    {
        if ($this->bucketsCache !== null) {
            return $this->bucketsCache;
        }

        $dates = [];

        foreach ($this->flattenRows($this->listFC) as $row) {
            if (!empty($row['mrp_date'])) {
                $dates[] = $row['mrp_date'];
            }
            foreach (array_keys($row['fc_months'] ?? []) as $month) {
                $first = $this->monthFirst($month);
                if ($first !== null) {
                    $dates[] = $first;
                }
            }
        }

        foreach ($this->flattenRows($this->listDS) as $row) {
            if (!empty($row['mrp_date'])) {
                $dates[] = $row['mrp_date'];
            }
        }

        if (empty($dates)) {
            return $this->bucketsCache = [];
        }

        sort($dates);

        return $this->bucketsCache = $this->buildMrpBuckets(
            $dates[0],
            $dates[count($dates) - 1]
        );
    }

    /**
     * The first day of a YYYYMM month as Y-m-d, or null for an invalid month.
     */
    protected function monthFirst(string $month): ?string
    {
        if (preg_match('/^20\d{2}(0[1-9]|1[0-2])$/', $month) !== 1) {
            return null;
        }

        return substr($month, 0, 4) . '-' . substr($month, 4, 2) . '-01';
    }

    /**
     * Flattened rows read from the "Forecast" files.
     *
     * @return array<int, array>
     */
    public function forecastRows(): array
    {
        return $this->flattenRows($this->listFC);
    }

    /**
     * Flattened rows read from the schedule ("DS Ex*") files.
     *
     * @return array<int, array>
     */
    public function scheduleRows(): array
    {
        return $this->flattenRows($this->listDS);
    }

    /**
     * One row per item + MRP date. Each row is a plain array matching headings():
     * the fixed columns first, then one value per semi-monthly bucket.
     *
     * Forecast quantities are placed on the 1st of their column month; DS
     * quantities on the smallest semi-monthly cutoff on/after the MRP date. The
     * DS value wins when both land in the same bucket.
     *
     * Background bands: the DS range (first bucket -> DS qty bucket) is light
     * green, the FC range (first FC month -> last FC month) is light red, and
     * cells covered by both are light brown. FC cells also carry an inc/dec font
     * colour based on each bucket versus the same bucket on the item's previous
     * MRP date.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $buckets = $this->buckets();
        $bucketIndex = array_flip($buckets);

        $items = [];
        $ensure = function (array $row) use (&$items) {
            $itemKey = $row['item_stxi'] ?? $row['item_yeid'] ?? null;
            if ($itemKey === null || $itemKey === '') {
                return null;
            }
            $itemKey = (string) $itemKey;
            $mrpDate = $row['mrp_date'] ?? null;

            // Pivot: one row per item per MRP date.
            $key = $itemKey . '|' . ($mrpDate ?? '');

            if (!isset($items[$key])) {
                $items[$key] = [
                    'key' => $key,
                    'item_stxi' => $row['item_stxi'] ?? null,
                    'item_yeid' => $row['item_yeid'] ?? null,
                    'maker_pn' => $row['maker_pn'] ?? null,
                    'description' => $row['description'] ?? null,
                    'maker_name' => $row['maker_name'] ?? null,
                    'supplier_name' => $row['supplier_name'] ?? null,
                    'lt' => $row['lt'] ?? null,
                    'mrp_date' => $mrpDate,
                    'fc' => [],
                    'ds' => [],
                    'fc_total' => 0.0,
                    'fonts' => [],
                ];
            }

            return $key;
        };

        foreach ($this->flattenRows($this->listFC) as $row) {
            $key = $ensure($row);
            if ($key === null) {
                continue;
            }
            foreach (($row['fc_months'] ?? []) as $month => $qty) {
                $first = $this->monthFirst($month);
                if ($first === null) {
                    continue;
                }
                $bucket = $this->resolveMrpBucket($first, $buckets);
                if ($bucket === null) {
                    continue;
                }

                $qty = (float) $qty;
                $items[$key]['fc'][$bucket] = ($items[$key]['fc'][$bucket] ?? 0) + $qty;
                $items[$key]['fc_total'] += $qty;
            }
        }

        foreach ($this->flattenRows($this->listDS) as $row) {
            $key = $ensure($row);
            if ($key === null) {
                continue;
            }
            // Bucket the schedule quantity by the file/folder MRP date; fall
            // back to the row's indicated date when the MRP date is missing.
            $date = $row['mrp_date'] ?? $row['indicated_date'] ?? null;
            if (empty($date)) {
                continue;
            }
            $bucket = $this->resolveMrpBucket($date, $buckets);
            if ($bucket === null) {
                continue;
            }

            $qty = $row['qty'] ?? null;
            $items[$key]['ds'][$bucket] = ($items[$key]['ds'][$bucket] ?? 0) + (float) ($qty ?? 0);
        }

        $this->computeFontColors($items);

        $this->dataRows = array_keys($items);
        $this->cellSources = [];
        $this->cellBg = [];
        $this->cellFonts = [];

        return collect($items)->map(function (array $item) use ($buckets, $bucketIndex) {
            $out = [
                $item['item_stxi'],
                $item['item_yeid'],
                $item['maker_pn'],
                $item['description'],
                $item['maker_name'],
                $item['supplier_name'],
                $item['lt'],
                $item['mrp_date'] ? date('d M Y', strtotime($item['mrp_date'])) : null,
            ];

            // The DS range is anchored on the row's MRP date.
            $mrpBucketDate = !empty($item['mrp_date']) ? $this->resolveMrpBucket($item['mrp_date'], $buckets) : null;
            $mrpBucket = $mrpBucketDate !== null ? ($bucketIndex[$mrpBucketDate] ?? null) : null;

            foreach ($buckets as $index => $bucket) {
                $cellId = $item['key'] . '|' . $bucket;

                // Every bucket shows a value (0 when there is none). DS wins a
                // collision; otherwise FC (including 0) is shown.
                $fc = $item['fc'][$bucket] ?? null;
                $ds = $item['ds'][$bucket] ?? null;

                $hasFc = array_key_exists($bucket, $item['fc']);
                $hasDs = array_key_exists($bucket, $item['ds']);

                if ($ds !== null && $ds != 0) {
                    $out[] = $ds;
                    $this->cellSources[$cellId] = ($hasFc && $fc != 0) ? 'both' : 'ds';
                } elseif ($hasFc) {
                    $out[] = $fc;
                    $this->cellSources[$cellId] = 'fc';
                } elseif ($hasDs) {
                    $out[] = $ds;
                    $this->cellSources[$cellId] = 'ds';
                } else {
                    // Float zero — an integer 0 is skipped by the writer and
                    // would leave the cell blank.
                    $out[] = 0.0;
                }

                // Background: green from the first bucket up to the MRP date
                // bucket, brown there where the FC also has qty, red on every
                // bucket after it.
                if ($mrpBucket !== null && $index <= $mrpBucket) {
                    $this->cellBg[$cellId] = ($fc !== null && $fc != 0) ? 'brown' : 'green';
                } else {
                    $this->cellBg[$cellId] = 'red';
                }

                $font = $item['fonts'][$bucket] ?? null;
                $source = $this->cellSources[$cellId] ?? null;
                if ($font !== null && $source !== 'ds' && $source !== 'both') {
                    $this->cellFonts[$cellId] = $font;
                }
            }

            return $out;
        })->values();
    }

    /**
     * Flag each row's FC cells per bucket versus the same bucket on the same
     * item's previous MRP date: a drop of at least dec% turns the font red, a
     * rise of at least inc% turns it indigo. A bucket whose quantity is no
     * longer reported (present before, absent now) counts as a drop to 0, and
     * the first row of an item is left untouched. Buckets that were 0 on the
     * previous date are skipped (no baseline).
     *
     * @param array<string, array> $items
     */
    private function computeFontColors(array &$items): void
    {
        $groups = [];
        foreach ($items as $key => $item) {
            $itemId = (string) ($item['item_stxi'] ?? $item['item_yeid'] ?? '');
            $groups[$itemId][] = $key;
        }

        foreach ($groups as $keys) {
            usort($keys, fn ($a, $b) => strcmp((string) $items[$a]['mrp_date'], (string) $items[$b]['mrp_date']));

            $previous = null;
            foreach ($keys as $key) {
                $current = $items[$key]['fc'];

                if ($previous !== null && !empty($previous)) {
                    $fonts = [];

                    // Buckets the row still reports, including explicit zeros.
                    foreach ($current as $bucket => $qty) {
                        $font = $this->changeFont((float) $qty, (float) ($previous[$bucket] ?? 0));
                        if ($font !== null) {
                            $fonts[$bucket] = $font;
                        }
                    }

                    // Buckets dropped entirely: present before, gone now (0).
                    foreach ($previous as $bucket => $prevQty) {
                        if (array_key_exists($bucket, $current)) {
                            continue;
                        }
                        $font = $this->changeFont(0.0, (float) $prevQty);
                        if ($font !== null) {
                            $fonts[$bucket] = $font;
                        }
                    }

                    if (!empty($fonts)) {
                        $items[$key]['fonts'] = $fonts;
                    }
                }

                $previous = $current;
            }
        }
    }

    /**
     * Font flag for a bucket given its current and previous quantity, or null
     * when the change stays within the thresholds. A bucket that carried no
     * forecast before (0) and now has qty has no percentage baseline and is
     * treated as a rise (indigo).
     */
    private function changeFont(float $current, float $previous): ?string
    {
        if ($previous == 0.0) {
            return ($current > 0 && $this->inc > 0) ? 'indigo' : null;
        }

        $change = ($current - $previous) / $previous * 100;

        if ($this->dec > 0 && $change <= -$this->dec) {
            return 'red';
        }
        if ($this->inc > 0 && $change >= $this->inc) {
            return 'indigo';
        }

        return null;
    }

    /**
     * Apply formatting after the sheet is written: borders everywhere, bold
     * header, background bands (green DS range, red FC range, brown overlap) and
     * the inc/dec font colour on FC cells.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $buckets = $this->buckets();

                $sheet = $event->sheet->getDelegate();
                $lastCol = Coordinate::stringFromColumnIndex(count(self::BASE_HEADINGS) + count($buckets));
                $lastRow = 3 + count($this->dataRows);

                $sheet->getStyle('A3:' . $lastCol . $lastRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);

                $sheet->getStyle('A3:' . $lastCol . '3')->applyFromArray([
                    'font' => ['bold' => true],
                ]);

                $sheet->getStyle('A1:A2')->getFont()->setBold(true);

                $backgrounds = [
                    'green' => 'C6EFCE', // light green (DS range)
                    'red' => 'FFC7CE',   // light red (FC range)
                    'brown' => 'DEB887', // light brown (DS + FC overlap)
                ];

                $fonts = [
                    'red' => 'FF0000',
                    'indigo' => '4B0082',
                ];

                $baseCount = count(self::BASE_HEADINGS);
                foreach ($this->dataRows as $rowIndex => $itemKey) {
                    foreach ($buckets as $bucketIndex => $bucket) {
                        $cellId = $itemKey . '|' . $bucket;

                        // Data rows start after the 3 header rows.
                        $excelRow = $rowIndex + 4;
                        $excelCol = Coordinate::stringFromColumnIndex($baseCount + $bucketIndex + 1);
                        $cell = $excelCol . $excelRow;

                        $bg = $this->cellBg[$cellId] ?? null;
                        if ($bg !== null) {
                            $sheet->getStyle($cell)
                                ->getFill()
                                ->applyFromArray([
                                    'fillType' => 'solid',
                                    'rotation' => 0,
                                    'color' => ['rgb' => $backgrounds[$bg]],
                                ]);
                        }

                        $font = $this->cellFonts[$cellId] ?? null;
                        if ($font !== null) {
                            $sheet->getStyle($cell)->getFont()->getColor()->setRGB($fonts[$font]);
                        }
                    }
                }
            },
        ];
    }
}
