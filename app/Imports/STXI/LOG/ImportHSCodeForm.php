<?php

namespace App\Imports\STXI\LOG;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Facades\DB;
use Redis;

use App\Models\STXI\LOG\HSCodeUplMaster;

use App\Traits\STXI\LOG\INSWTraits;

class ImportHSCodeForm implements ToModel, WithEvents
{
    use INSWTraits;
    protected $username, $issdate, $keys, $doc, $type, $keysFordoc, $item, $bg, $series, $mkhscd, $stxihscd;
    private $activeSheetTitle;
    function __construct($username, $issdate = '', $keys = 0, $doc = '', $type = '', $keysFordoc = 0, $item = '', $bg = '', $series = '', $mkhscd = '', $stxihscd = '')
    {
        $this->username = $username;
        $this->issdate = $issdate;
        $this->keys = $keys;
        $this->keysFordoc = $keysFordoc;
        $this->doc = $doc;
        $this->type = $type;

        // For single input
        $this->item = $item;
        $this->bg = $bg;
        $this->series = $series;
        $this->mkhscd = $mkhscd;
        $this->stxihscd = $stxihscd;
    }
    /**
     * @param Collection $collection
     */
    public function model(array $row)
    {
        foreach ($row as $key => $valData) {
            if (str_contains($valData, 'Document No') && empty($this->doc)) {
                $this->doc = empty($this->type) ? $row[$key + 4] : $row[$key + 2];

                break;
            }
        }

        if (empty($this->type)) {
            foreach ($row as $key => $valData) {
                if (str_contains($valData, 'ANALYSIS REPORT')) {
                    $this->type = 'single';
                }
            }

            foreach ($row as $key => $valData) {
                if (str_contains($valData, 'INFORMATION HS CODE')) {
                    $this->type = 'batch';
                }
            }
        }

        // For Single HS Code Forms
        if ($this->type === 'single' && $this->keys >= 1) {
            foreach ($row as $key => $valData) {
                if (str_contains($valData, 'Issue Date') && !empty($row[$key + 5])) {
                    $this->issdate = $row[$key + 5];
                }

                if (str_contains($valData, 'Parts Code') && !empty($row[$key + 5])) {
                    $this->item = $row[$key + 5];
                }

                if (str_contains($valData, 'Maker HS Code') && !empty($row[$key + 5])) {
                    $this->mkhscd = str_replace('.', '', $row[$key + 5]);
                }

                if (str_contains($valData, 'SERIES') && !empty($row[$key + 2])) {
                    $this->series = $row[$key + 2];
                }

                if (str_contains($valData, 'BG') && !empty($row[$key + 2])) {
                    $this->bg = $row[$key + 2];
                }
            }

            if (str_contains($this->getActiveSheetTitle(), 'Attachment')) {
                if ($this->keys > 5 && !empty($this->stxihscd) && empty($this->item) && !empty($row[10])) {
                    HSCodeUplMaster::where('HSCD_BG', $this->bg)
                        ->where('HSCD_ITMCD', (string)$row[3])
                        ->delete();

                    HSCodeUplMaster::create([
                        'p_u_username' => $this->username,
                        'HSCD_DOCNO' => $this->doc,
                        'HSCD_BG' => $this->bg,
                        'HSCD_ITMCD' => (string)$row[3],
                        'HSCD_SERIES' => $row[7],
                        'HSCD_MKHSCD' => $row[10],
                        'HSCD_STXICD' => $this->stxihscd,
                        'HSCD_UPLTYFORM' => $this->type,
                        'HSCD_ISSDT' => $this->issdate
                    ]);

                    $this->syncINSWDataShare($this->mkhscd);
                    $this->syncINSWDataShare($this->stxihscd);
                }
            } else {
                if ($this->keys === 63) {
                    if (empty($this->stxihscd)) {
                        $this->stxihscd = str_replace('.', '', $row[0]);
                    }

                    if (!empty($this->item)) {
                        HSCodeUplMaster::where('HSCD_BG', $this->bg)->where('HSCD_ITMCD', $this->item)->delete();
                        HSCodeUplMaster::create([
                            'p_u_username' => $this->username,
                            'HSCD_DOCNO' => $this->doc,
                            'HSCD_BG' => $this->bg,
                            'HSCD_ITMCD' => $this->item,
                            'HSCD_SERIES' => $this->series,
                            'HSCD_MKHSCD' => $this->mkhscd,
                            'HSCD_STXICD' => str_replace('.', '', $row[0]),
                            'HSCD_UPLTYFORM' => $this->type,
                            'HSCD_ISSDT' => $this->issdate
                        ]);

                        $this->syncINSWDataShare($this->mkhscd);
                        $this->syncINSWDataShare(str_replace('.', '', $row[0]));
                    }
                }
            }
        }

        // For Multiple HS Code form
        if ($this->type === 'batch' && $this->keys >= 2) {
            foreach ($row as $key => $valData) {
                if (str_contains($valData, 'BG') && !empty($row[$key + 2])) {
                    $this->bg = trim(str_replace(': ', '', $row[$key + 2]));
                }

                if (str_contains($valData, 'Issue Date') && !empty($row[$key + 2])) {
                    $this->issdate = date('Y-m-d', strtotime(trim(str_replace(': ', '', $row[$key + 2]))));
                }
            }

            if ($this->keys >= 12 && !empty($row[1])) {
                HSCodeUplMaster::where('HSCD_BG', $this->bg)
                    ->where('HSCD_ITMCD', $row[1])
                    ->delete();

                HSCodeUplMaster::create([
                    'p_u_username' => $this->username,
                    'HSCD_DOCNO' => trim(str_replace(':', '', $this->doc)),
                    'HSCD_BG' => $this->bg,
                    'HSCD_ITMCD' => $row[1],
                    'HSCD_SERIES' => $row[5],
                    'HSCD_MKHSCD' => $row[9],
                    'HSCD_STXICD' => str_replace('.', '', $row[12]),
                    'HSCD_UPLTYFORM' => $this->type,
                    'HSCD_ISSDT' => $this->issdate
                ]);
                $this->syncINSWDataShare($row[9]);
                $this->syncINSWDataShare(str_replace('.', '', $row[12]));
            }
        }

        $this->keys = $this->keys + 1;
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                if ($event->getSheet()->getTitle() !== $this->activeSheetTitle) {
                    $this->keys = 0;
                }
                $this->activeSheetTitle = $event->getSheet()->getTitle();
            },
        ];
    }

    public function getActiveSheetTitle()
    {
        return $this->activeSheetTitle;
    }
}
