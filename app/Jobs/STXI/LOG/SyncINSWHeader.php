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
                'Authorization' => 'Basic aW5zd18yOmJhYzJiYXM2'
            ]
        ]);

        $res = $guzz->request('GET', $endpoint);

        $content['CURL'] = json_decode($res->getBody(), true);

        return $content['CURL'];
    }
}
