<?php

namespace App\Jobs\STXI\LOG;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncINSWHeader implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $search;
    /**
     * Create a new job instance.
     */
    public function __construct($search = '')
    {
        $this->search = $search;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', '10800');
        $data = $this->getData($this->search, 10000)['data'][0]['result'];

        $hasilData = [];
        foreach ($data as $key => $value) {
            SyncINSWDetail::dispatch($value['_source']['hs_code_format'], $this->search)->onQueue('INSWQueueRunningDetail');
        }
    }

    public function getData($hsCode, $size)
    {
        $endpoint = 'https://api.insw.go.id/api/cms/hscode?keyword=' . $hsCode . '&size=' . $size . '&from=0';

        $content = [];
        $guzz = new \GuzzleHttp\Client([
            'headers' => [
                'Authorization' => 'Basic eyJhbGciOiJSUzI1NiIsInR5cCI6ImJzYStqd3QiLCJraWQiOiJoUUNJcUJsWXRTYVZ1UjFWaFpGWGZpTWdrR2NoLS1hMG84NU5OTmxMR2xnIn0.eyJpYXQiOjE3NTc1NzI3MjksImV4cCI6MTc1NzYwOTk5OSwiYXVkIjoiaHR0cHM6Ly9pbnN3LmdvLmlkIiwiaXNzIjoiaHR0cHM6Ly9zc28uaW5zdy5nby5pZCIsInN1YiI6ImEwYWMwOWZhLTU2NzMtNDJjNi05ZmUxLTQxMWU2YzkzNzEwYSIsImp0aSI6IlUyRnNkR1ZrWDEvK2JvUTduZ3pnTHBSMWJRblNXVEFxYUw5dlVWZ2VpR3QvZG1PbWE3cm02NWRGN2Q5a0xpMUNWdEI4Qkp2ZG5mcmk1bVR2ZEZEeEVNTjBPZDBZWHl1Tm8zUktFcHBIVkhUczFicGN4T2dtNEU3bEpja3hDWEpBbk5xc0xCSmVTdjBJTE4yOFVUVlROVmtJdVBKTUpDMUxWRnJuMHJJMzhSRVkxbVBOYkVkajFiaFpMUHIyZmVmWk4vOVZkV3l3ei9TRnZqcmt1L0FVQnJyQ3o5dVNQR2dscndpTXBRZGlQNk9wQnhmWmJnclNOY3dEcFJISmpaUkkifQ.NQ24Rpuppzqq5viOmFh3aVgTpTWDZj-VCOVxSpePuJV_GxmPgRVJTimr_5D_GxNFNHm2S4bpxPA6VJtADapsi1S90cdBFuw76d6cq_Za-5Gpxftwfv26JT5PmRdU5ctXloZXqJvyRo4fL9PTrAi2m5-qSfMCWoXayHp-S4OKorxWKPeB2OOdeIedsBtEplETLhIQBj8QC8zq_rj_ucGI7iADbkexZyixNe3wSaR8sOjaigZO97goHoQd3SWIoPz-1QZZECutEueOOCNOiijFYYKkRxr5GYt8HbjZDL6SttKN4BReqlle2bjn0LTX8-gYO3hLbJiMWup3maSJM94HNA',
                'Origin' => 'https://api.insw.go.id',
                'Referer' => 'https://api.insw.go.id'
            ]
        ]);

        $res = $guzz->request('GET', $endpoint);

        $content['CURL'] = json_decode($res->getBody(), true);

        return $content['CURL'];
    }
}
