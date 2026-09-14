<?php

namespace App\Exports\STXI\EMS2;

use Maatwebsite\Excel\Concerns\FromCollection;

class ExportDeliveryScheduleComp implements FromCollection
{
    /**
     * Raw rows read from all matched spreadsheets.
     *
     * Two shapes are supported:
     * - grouped (controller default): [<file path> => [ [cell, ...], ... ]]
     * - flat:                         [ [cell, ...], ... ]
     *
     * @var array
     */
    protected $rows;

    /**
     * Request context: inc, dec, bg, folders, patterns, files.
     *
     * @var array
     */
    protected $context;

    public function __construct(array $rows = [], array $context = [])
    {
        $this->rows = $rows;
        $this->context = $context;
    }

    /**
     * True when $this->rows is keyed by source filename instead of a flat list.
     */
    protected function rowsAreGrouped(): bool
    {
        $rows = $this->rows;

        return !empty($rows) && !array_is_list($rows);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        logger('rows', $this->rows);

        // TODO: parse/compare $this->rows using $this->context
        // (e.g. thresholds inc/dec, bg, source folders).

        // Rows are grouped per source filename. Keep the grouped map on
        // $this->rows for per-file comparison, but flatten for this single-sheet
        // export so the output stays one row per spreadsheet row.
        if ($this->rowsAreGrouped()) {
            $flattened = [];
            foreach ($this->rows as $fileRows) {
                foreach ((array) $fileRows as $row) {
                    $flattened[] = $row;
                }
            }

            return collect($flattened);
        }

        return collect($this->rows);
    }
}
