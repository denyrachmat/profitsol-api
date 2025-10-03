<?php

namespace App\Jobs\STXI\LOG;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Excel;
use Redis;
use App\Exports\STXI\LOG\ExportHSCodeReport;
use App\Models\STXI\LOG\HSCodeUplMaster;
use PDF;
use Illuminate\Http\Request;

class ExportHSCodeQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $filter;
    public $withHist;
    public $type;
    public $username;

    /**
     * Create a new job instance.
     */
    public function __construct($filter, $withHist = false, $type = 'excel', $username = '')
    {
        $this->filter = $filter;
        $this->withHist = $withHist;
        $this->type = $type;
        $this->username = $username;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        ini_set('max_execution_time', '3000');

        $codeQueue = 'HSC_' . uniqid() . '_' . rand(1000, 9999);

        Redis::publish('portalv2', json_encode([
            'app' => 'hs_code',
            'username' => $this->username,
            'message' => 'start download HS Code export now...',
            'type' => 'info',
            'status' => 'hs_code_export_start',
            'code_queue' => $codeQueue
        ]));

        $download = '';
        if ($this->type === 'excel') {
            $datetime = date('y-m-d his');
            Excel::store(new ExportHSCodeReport($this->filter, $this->withHist), 'export_hscode_' . $datetime . '.xlsx', 'public');

            $download = 'storage/export_hscode_' . $datetime . '.xlsx';
        } else {
            $arrReq = array_merge($this->filter, [
                'select' => [
                    '*'
                ],
                'with' => 'insw_reg'
            ]);

            $data = $this->HSCodeFilter(new Request($arrReq));

            $hasil = [];
            foreach ($data as $keyData => $valueData) {
                $listImport = '';
                $listImportPost = '';
                if (count($valueData['insw_reg']) > 0) {
                    $arrImport = [];
                    $arrImportPost = [];
                    foreach ($valueData['insw_reg'] as $keyInswReg => $valueInswReg) {
                        if ($valueInswReg['ZIRD_TYPE'] == 'import_regulation') {
                            $arrImport[] = '- ' . $valueInswReg['ZIRD_NMIJIN'];
                        }

                        if ($valueInswReg['ZIRD_TYPE'] == 'import_regulation_post_border') {
                            $arrImportPost[] = '- ' . $valueInswReg['ZIRD_NMIJIN'];
                        }
                    }

                    $listImport = implode("<br>", $arrImport);
                    $listImportPost = implode("<br>", $arrImportPost);
                }

                $hasil[] = array_merge($valueData, [
                    'LIST_IMPORT' => $listImport,
                    'LIST_IMPORT_POST' => $listImportPost
                ]);
            }

            $pdf = PDF::loadView('STXI/LOG/hsCodeDraft', ['data' => $hasil])->setOrientation('landscape');
            $datetime = date('y-m-d his');

            // return view('STXI/LOG/hsCodeDraft', ['data' => $data]);

            $pdf->save(public_path('storage/hscode_export_' . $datetime . '.pdf'));

            $download = 'storage/hscode_draft_' . $datetime . '.pdf';
        }
        
        Redis::publish('portalv2', json_encode([
            'app' => 'hs_code',
            'username' => $this->username,
            'message' => 'HS Code export done, download will start shortly.',
            'type' => 'success',
            'status' => 'hs_code_export_done',
            'data' => $download,
            'code_queue' => $codeQueue
        ]));
    }

    public function HSCodeFilter(Request $request): array
    {
        ini_set('memory_limit', '2048M');
        $data = HSCodeUplMaster::join('CRPTWEB.dbo.VIEW_MITM_TBL', 'MITM_ITMCD', 'HSCD_ITMCD');

        if ($request->has('select')) {
            $data->select($request->select);
        }

        if (
            count($request->filter) > 0 && count(array_filter($request->filter, function ($f) {
                return !empty($f['value']);
            })) > 0
        ) {
            foreach ($request->filter as $key => $value) {
                if (isset($value['value'])) {
                    $data->where($value['cols'], $value['param'], $value['param'] === 'like' ? "%{$value['value']}%" : $value['value']);
                }
            }
        }

        if ($request->has('join')) {
            foreach ($request->join as $keyJoin => $valueJoin) {
                $data->join($valueJoin['table'], $valueJoin['localKey'], $valueJoin['foreignKey']);
            }
        }

        if ($request->has('with')) {
            $data->with($request->with);
        }

        if ($request->has('groupBy')) {
            $data->groupBy($request->select);
        }

        return $data->get()->toArray();
    }
}
