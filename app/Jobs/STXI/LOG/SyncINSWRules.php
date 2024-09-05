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

            logger('Cek hasil header INSWRules');
            logger(json_encode($data));
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
                } else {
                    if (!empty($this->findData)) {
                        SyncINSWHeader::dispatch($value['nomor_peraturan'])->onQueue('INSWQueueRunning');
                    }
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
                'Authorization' => 'Basic aW5zd18yOmJhYzJiYXM2'
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
                'Authorization' => 'Basic aW5zd18yOmJhYzJiYXM2'
            ]
        ]);

        $res = $guzz->request('GET', $endpoint);

        $content['CURL'] = json_decode($res->getBody(), true);

        return $content['CURL'];
    }
}
