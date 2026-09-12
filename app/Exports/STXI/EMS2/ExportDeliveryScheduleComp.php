<?php

namespace App\Exports\STXI\EMS2;

use Maatwebsite\Excel\Concerns\FromCollection;

class ExportDeliveryScheduleComp implements FromCollection
{
    /**
     * Raw rows read from all matched spreadsheets (each row is an array of cells).
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
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        logger('rows', $this->rows);
        // TODO: parse/compare $this->rows using $this->context
        // (e.g. thresholds inc/dec, bg, source folders).
        return collect($this->rows);
    }
}
