<?php

namespace App\Jobs\STXI\LOG;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\STXI\LOG\INSWDataRulesMaster;
use App\Jobs\STXI\LOG\SyncINSWHeader;
class SyncINSWRules implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $findData;
    /**
     * Create a new job instance.
     */
    public function __construct($findData = '')
    {
        $this->findData = $findData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $checkData = INSWDataRulesMaster::get();
        if (count($checkData) === 0) {
            $data = $this->getData();

            foreach ($data['data'] as $key => $value) {
                INSWDataRulesMaster::create([
                    'ZIRM_NO' => $value['nomor_peraturan'],
                    'ZIRM_TYPE' => $value['bidang_peraturan'],
                    'ZIRM_ISSDT' => date('Y-m-d', strtotime($value['created_date'])),
                    'ZIRM_FILE' => str_replace("./", "https://api.insw.go.id/", $value['file']),
                    'ZIRM_INSTANCE' => $value['instansi'],
                    'ZIRM_TITLEHEAD' => $value['jenis_peraturan'],
                    'ZIRM_TITLE' => $value['judul_peraturan'],
                    'ZIRM_STARTDT' => date('Y-m-d', strtotime($value['mulai_tanggal'])),
                    'ZIRM_RULEDT' => date('Y-m-d', strtotime($value['tanggal_peraturan'])),
                ]);
            }

            SyncINSWHeader::dispatch($this->findData)->onQueue('INSWQueueRunning');
        } else {
            $checkLatest = INSWDataRulesMaster::orderBy('ZIRM_STARTDT', 'desc')->first();

            $data = $this->getData();

            // logger('Cek hasil header INSWRules');
            // logger(json_encode($data));
            if (count($data['data']) > 0) {
                $value = $data['data'][0];
                if ($value['nomor_peraturan'] != $checkLatest->ZIRM_NO) {
                    INSWDataRulesMaster::create([
                        'ZIRM_NO' => $value['nomor_peraturan'],
                        'ZIRM_TYPE' => $value['bidang_peraturan'],
                        'ZIRM_ISSDT' => date('Y-m-d', strtotime($value['created_date'])),
                        'ZIRM_FILE' => str_replace(".", "https://api.insw.go.id", $value['file']),
                        'ZIRM_INSTANCE' => $value['instansi'],
                        'ZIRM_TITLEHEAD' => $value['jenis_peraturan'],
                        'ZIRM_TITLE' => $value['judul_peraturan'],
                        'ZIRM_STARTDT' => date('Y-m-d', strtotime($value['mulai_tanggal'])),
                        'ZIRM_RULEDT' => date('Y-m-d', strtotime($value['tanggal_peraturan'])),
                    ]);

                    SyncINSWHeader::dispatch($value['nomor_peraturan'])->onQueue('INSWQueueRunning');
                }
            } else {
                if (!empty($this->findData)) {
                    SyncINSWHeader::dispatch($this->findData)->onQueue('INSWQueueRunning');
                }
            }
        }
    }

    public function getData()
    {
        $endpoint = 'https://api.insw.go.id/api-prod-ba/ref/peraturan/search?keyword=' . $this->findData;

        $content = [];
        $guzz = new \GuzzleHttp\Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:143.0) Gecko/20100101 Firefox/143.0',
                'Accept' => 'application/json, text/plain, */*',
                'Accept-Language' => 'en,en-US;q=0.7,id;q=0.3',
                'Accept-Encoding' => 'gzip, deflate, br, zstd',
                'Authorization' => 'Basic eyJhbGciOiJSUzI1NiIsInR5cCI6ImJzYStqd3QiLCJraWQiOiJoUUNJcUJsWXRTYVZ1UjFWaFpGWGZpTWdrR2NoLS1hMG84NU5OTmxMR2xnIn0.eyJpYXQiOjE3NTc1NzI3MjksImV4cCI6MTc1NzYwOTk5OSwiYXVkIjoiaHR0cHM6Ly9pbnN3LmdvLmlkIiwiaXNzIjoiaHR0cHM6Ly9zc28uaW5zdy5nby5pZCIsInN1YiI6ImEwYWMwOWZhLTU2NzMtNDJjNi05ZmUxLTQxMWU2YzkzNzEwYSIsImp0aSI6IlUyRnNkR1ZrWDEvK2JvUTduZ3pnTHBSMWJRblNXVEFxYUw5dlVWZ2VpR3QvZG1PbWE3cm02NWRGN2Q5a0xpMUNWdEI4Qkp2ZG5mcmk1bVR2ZEZEeEVNTjBPZDBZWHl1Tm8zUktFcHBIVkhUczFicGN4T2dtNEU3bEpja3hDWEpBbk5xc0xCSmVTdjBJTE4yOFVUVlROVmtJdVBKTUpDMUxWRnJuMHJJMzhSRVkxbVBOYkVkajFiaFpMUHIyZmVmWk4vOVZkV3l3ei9TRnZqcmt1L0FVQnJyQ3o5dVNQR2dscndpTXBRZGlQNk9wQnhmWmJnclNOY3dEcFJISmpaUkkifQ.NQ24Rpuppzqq5viOmFh3aVgTpTWDZj-VCOVxSpePuJV_GxmPgRVJTimr_5D_GxNFNHm2S4bpxPA6VJtADapsi1S90cdBFuw76d6cq_Za-5Gpxftwfv26JT5PmRdU5ctXloZXqJvyRo4fL9PTrAi2m5-qSfMCWoXayHp-S4OKorxWKPeB2OOdeIedsBtEplETLhIQBj8QC8zq_rj_ucGI7iADbkexZyixNe3wSaR8sOjaigZO97goHoQd3SWIoPz-1QZZECutEueOOCNOiijFYYKkRxr5GYt8HbjZDL6SttKN4BReqlle2bjn0LTX8-gYO3hLbJiMWup3maSJM94HNA',
                'Origin' => 'https://insw.go.id',
                'Connection' => 'keep-alive',
                'Referer' => 'https://insw.go.id/',
                'Sec-Fetch-Dest' => 'empty',
                'Sec-Fetch-Mode' => 'cors',
                'Sec-Fetch-Site' => 'same-site',
                'If-None-Match' => 'W/"6d28-6m56MLyabkKGcY+hqixQFXrZ90g:dtagent10319250807130352az0t"',
                'Priority' => 'u=0'
            ]
        ]);

        $res = $guzz->request('GET', $endpoint);

        $content['CURL'] = json_decode($res->getBody(), true);

        return $content['CURL'];
    }

    public function getHomeData()
    {
        $endpoint = 'https://api.insw.go.id/api-prod-ba/ref/peraturan-home';

        $content = [];
        $guzz = new \GuzzleHttp\Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:143.0) Gecko/20100101 Firefox/143.0',
                'Accept' => 'application/json, text/plain, */*',
                'Accept-Language' => 'en,en-US;q=0.7,id;q=0.3',
                'Accept-Encoding' => 'gzip, deflate, br, zstd',
                'Authorization' => 'Basic eyJhbGciOiJSUzI1NiIsInR5cCI6ImJzYStqd3QiLCJraWQiOiJoUUNJcUJsWXRTYVZ1UjFWaFpGWGZpTWdrR2NoLS1hMG84NU5OTmxMR2xnIn0.eyJpYXQiOjE3NTc1NzI3MjksImV4cCI6MTc1NzYwOTk5OSwiYXVkIjoiaHR0cHM6Ly9pbnN3LmdvLmlkIiwiaXNzIjoiaHR0cHM6Ly9zc28uaW5zdy5nby5pZCIsInN1YiI6ImEwYWMwOWZhLTU2NzMtNDJjNi05ZmUxLTQxMWU2YzkzNzEwYSIsImp0aSI6IlUyRnNkR1ZrWDEvK2JvUTduZ3pnTHBSMWJRblNXVEFxYUw5dlVWZ2VpR3QvZG1PbWE3cm02NWRGN2Q5a0xpMUNWdEI4Qkp2ZG5mcmk1bVR2ZEZEeEVNTjBPZDBZWHl1Tm8zUktFcHBIVkhUczFicGN4T2dtNEU3bEpja3hDWEpBbk5xc0xCSmVTdjBJTE4yOFVUVlROVmtJdVBKTUpDMUxWRnJuMHJJMzhSRVkxbVBOYkVkajFiaFpMUHIyZmVmWk4vOVZkV3l3ei9TRnZqcmt1L0FVQnJyQ3o5dVNQR2dscndpTXBRZGlQNk9wQnhmWmJnclNOY3dEcFJISmpaUkkifQ.NQ24Rpuppzqq5viOmFh3aVgTpTWDZj-VCOVxSpePuJV_GxmPgRVJTimr_5D_GxNFNHm2S4bpxPA6VJtADapsi1S90cdBFuw76d6cq_Za-5Gpxftwfv26JT5PmRdU5ctXloZXqJvyRo4fL9PTrAi2m5-qSfMCWoXayHp-S4OKorxWKPeB2OOdeIedsBtEplETLhIQBj8QC8zq_rj_ucGI7iADbkexZyixNe3wSaR8sOjaigZO97goHoQd3SWIoPz-1QZZECutEueOOCNOiijFYYKkRxr5GYt8HbjZDL6SttKN4BReqlle2bjn0LTX8-gYO3hLbJiMWup3maSJM94HNA',
                'Origin' => 'https://insw.go.id',
                'Connection' => 'keep-alive',
                'Referer' => 'https://insw.go.id/',
                'Sec-Fetch-Dest' => 'empty',
                'Sec-Fetch-Mode' => 'cors',
                'Sec-Fetch-Site' => 'same-site',
                'If-None-Match' => 'W/"6d28-6m56MLyabkKGcY+hqixQFXrZ90g:dtagent10319250807130352az0t"',
                'Priority' => 'u=0'
            ]
        ]);

        $res = $guzz->request('GET', $endpoint);

        $content['CURL'] = json_decode($res->getBody(), true);

        return $content['CURL'];
    }
}
