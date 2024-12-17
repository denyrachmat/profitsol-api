<?php

namespace App\Jobs\STXI\LOG;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\STXI\LOG\INSWDataMaster;
use App\Models\STXI\LOG\INSWDataRegDet;
class SyncINSWRegDetail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $hsCode;
    /**
     * Create a new job instance.
     */
    public function __construct($hsCode)
    {
        $this->hsCode = $hsCode;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $getRegHSCOde = INSWDataRegDet::where('ZID_HSCODE', $this->hsCode)->first();

        if (empty($getRegHSCOde)) {
            $cekDataINSW = $this->getData($this->hsCode);
            if (!empty($cekDataINSW)) {
                if (isset($cekDataINSW['data'][0])) {
                    $getDataINSW = $cekDataINSW['data'][0];

                    $dataRegCreate = [];
                    $getHSCode = $this->hsCode;
                    foreach ($getDataINSW['import_regulation'] as $key => $valueReg) {
                        $dataRegCreate[] = INSWDataRegDet::create([
                            'ZID_HSCODE' => $getHSCode,
                            'ZIRD_TYPE' => 'import_regulation',
                            'ZIRD_NMIJIN' => $valueReg['nama_ijin'] ?? $valueReg['name'],
                            'ZIRD_KDIJIN' => $valueReg['kd_ijin'],
                            'ZIRD_DESC' => $valueReg['desc'] ?? $valueReg['deskripsi'],
                            'ZIRD_BEALIST' => json_encode($valueReg['dok_pabean']),
                            'ZIRD_LEGAL' => $valueReg['legal'] ?? '',
                            'ZIRD_MODUL' => $valueReg['modul'],
                            'ZIRD_SKEPNO' => $valueReg['nomor_skep'] ?? ''
                        ]);
                    }

                    foreach ($getDataINSW['import_regulation_border'] as $key2 => $valueRegBord) {
                        $dataRegCreate[] = INSWDataRegDet::create([
                            'ZID_HSCODE' => $getHSCode,
                            'ZIRD_TYPE' => 'import_regulation_border',
                            'ZIRD_NMIJIN' => $valueRegBord['nama_ijin'] ?? $valueRegBord['name'],
                            'ZIRD_KDIJIN' => $valueRegBord['kd_ijin'],
                            'ZIRD_DESC' => $valueRegBord['desc'] ?? $valueRegBord['deskripsi'],
                            'ZIRD_BEALIST' => json_encode($valueRegBord['dok_pabean']),
                            'ZIRD_LEGAL' => $valueRegBord['legal'] ?? '',
                            'ZIRD_MODUL' => $valueRegBord['modul'],
                            'ZIRD_SKEPNO' => $valueRegBord['nomor_skep'] ?? ''
                        ]);
                    }

                    foreach ($getDataINSW['import_regulation_post_border'] as $key3 => $valueRegPostBord) {
                        $dataRegCreate[] = INSWDataRegDet::create([
                            'ZID_HSCODE' => $getHSCode,
                            'ZIRD_TYPE' => 'import_regulation_post_border',
                            'ZIRD_NMIJIN' => $valueRegPostBord['nama_ijin'] ?? $valueRegPostBord['name'],
                            'ZIRD_KDIJIN' => $valueRegPostBord['kd_ijin'],
                            'ZIRD_DESC' => $valueRegPostBord['desc'] ?? $valueRegPostBord['deskripsi'],
                            'ZIRD_BEALIST' => json_encode($valueRegPostBord['dok_pabean']),
                            'ZIRD_LEGAL' => $valueRegPostBord['legal'] ?? '',
                            'ZIRD_MODUL' => $valueRegPostBord['modul'],
                            'ZIRD_SKEPNO' => $valueRegPostBord['nomor_skep'] ?? ''
                        ]);
                    }

                    foreach ($getDataINSW['export_regulation'] as $key4 => $valueExport) {
                        $dataRegCreate[] = INSWDataRegDet::create([
                            'ZID_HSCODE' => $getHSCode,
                            'ZIRD_TYPE' => 'export_regulation',
                            'ZIRD_NMIJIN' => $valueExport['nama_ijin'] ?? $valueExport['name'],
                            'ZIRD_KDIJIN' => $valueExport['kd_ijin'],
                            'ZIRD_DESC' => $valueExport['desc'] ?? $valueExport['deskripsi'],
                            'ZIRD_BEALIST' => json_encode($valueExport['dok_pabean']) ?? '',
                            'ZIRD_LEGAL' => $valueExport['legal'] ?? '',
                            'ZIRD_MODUL' => $valueExport['modul'],
                            'ZIRD_SKEPNO' => $valueExport['nomor_skep'] ?? ''
                        ]);
                    }
                }
            }
        }
    }


    public function getData($hsCode)
    {
        $endpoint = 'https://api.insw.go.id/api-prod-ba/ref/hscode/komoditas?hs_code=' . $hsCode;

        $content = [];
        $guzz = new \GuzzleHttp\Client([
            'headers' => [
                'Authorization' => 'Basic aW5zd18yOmJhYzJiYXM2'
            ]
        ]);

        $res = $guzz->request('GET', $endpoint);

        $content['CURL'] = json_decode($res->getBody(), true);

        return $content['CURL'];
    }
}
