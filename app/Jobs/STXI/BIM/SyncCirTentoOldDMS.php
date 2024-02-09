<?php

namespace App\Jobs\STXI\BIM;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Redis;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;

use App\Models\STXI\BIM\CircularTenMstr;
use App\Models\STXI\BIM\CircularTenModelDet;

class SyncCirTentoOldDMS implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;
    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->sendToDMS($this->data['ten'], $this->data['mail_date']);
        } catch (ClientException $e) {
            Redis::publish('portalv2', json_encode([
                'app' => 'cirten',
                'message' => 'TEN ' . $this->data['ten'] . ' : sync failed server (' . $e->getMessage() . ')',
                'type' => 'red',
                'status' => 'failed',
            ]));
        }
    }

    

    public function generateDocument($emailDate, $isExport = false)
    {
        if ($isExport) {
            $pdf = Pdf::loadView('STXI/BIM/circularTenLayout', $this->data);

            return $pdf->download($this->data['ten'] . '.pdf');
        }

        return $this->data;
    }

    public function sendToDMS($ten, $emailDate)
    {
        // Upload PDF to DMS
        $pdf = $this->generateDocument($emailDate, true);
        $storepdf = Storage::disk('local')->put('/public/circular_ten/' . $ten . '/' . $ten . '.pdf', $pdf);
        $target_url = 'http://192.168.100.32:8081/stx_api/public/api/'; // Write your URL here
        $pathFile = Storage::disk('local')->url('circular_ten/' . $ten . '/' . $ten . '.pdf');

        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $target_url,
            // You can set any number of default request options.
            'timeout' => 2.0,
        ]);

        try {
            $getModelList = $this->generateDocument($emailDate);
            $model = $getModelList['model'];
            $sch = $getModelList['exec_sch'];
            $reason = $getModelList['reason'];
            $content = $getModelList['content'];

            if (!empty($model) && !empty($sch) && !empty($reason) && !empty($content)) {
                $res = $client->request('POST', 'dms/docsupload', [
                    'multipart' => [
                        [
                            'name' => 'username',
                            'contents' => 'susi',
                            'headers' => ['Content-Type' => 'application/json']
                        ],
                        [
                            'name' => 'folder_id',
                            'contents' => '2vxtcJxq4YDBmS5v23cKaWRU4o01LXsUtBPtU9jWm2x9NklzyD',
                            'headers' => ['Content-Type' => 'application/json']
                        ],
                        [
                            'name' => 'folder_name',
                            'contents' => "New System Cirten (Don't Delete)",
                            'headers' => ['Content-Type' => 'application/json']
                        ],
                        [
                            'name' => 'file',
                            'contents' => Psr7\Utils::tryFopen($pathFile, 'r'),
                            'headers' => ['Content-Type' => 'application/pdf']
                        ],
                    ],
                ]);

                $uploadResult = $res->getBody();
                $resApproveDoc = $client->request('GET', 'dms/toggleapprovedocflag/' . $uploadResult . '/1');

                CircularTenMstr::where('CIRTEN_NO', $ten)->update([
                    'CIRTEN_DMS_DOC_ID' => $uploadResult
                ]);

                Redis::publish('portalv2', json_encode([
                    'app' => 'cirten',
                    'message' => 'TEN ' . $this->data['ten'] . ' : has been uploaded to DMS, please check DMS Apps !',
                    'type' => 'green',
                    'status' => 'success',
                ]));
            } else {
                Redis::publish('portalv2', json_encode([
                    'app' => 'cirten',
                    'message' => 'TEN ' . $this->data['ten'] . ' : Some data for ten is not recognized yet !!!',
                    'data' => [
                        'model' => $model,
                        'sch' => $sch,
                        'reason' => $reason,
                        'content' => $content,
                    ],
                    'type' => 'green',
                    'status' => 'success',
                ]));
            }
        } catch (ClientException $e) {
            Redis::publish('portalv2', json_encode([
                'app' => 'cirten',
                'message' => 'TEN ' . $this->data['ten'] . ' : sync failed server (' . $e->getMessage() . ')',
                'type' => 'red',
                'status' => 'failed',
            ]));
        }
    }
}
