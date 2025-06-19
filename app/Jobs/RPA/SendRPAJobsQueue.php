<?php

namespace App\Jobs\RPA;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\PORTAL\PortalRPAHist;
use App\Models\PORTAL\PortalRPAMaster;
use Redis;

class SendRPAJobsQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $id;
    /**
     * Create a new job instance.
     */
    public function __construct($id = '')
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $getHist = PortalRPAHist::where('portal_rpa_hist.id', $this->id)
            ->join('portal_rpa_mstr', 'portal_rpa_mstr.id', '=', 'portal_rpa_hist.prh_prmid')
            ->where('prh_flag', 0)
            ->first();

        if ($getHist->prh_flag == 0) {
            if ($getHist->prm_type == 'api') {
                $this->sendApi($getHist->id);
            }
        } else {
            Redis::publish('portalv2', json_encode([
                'app' => 'rpa',
                'message' => 'RPA Job ID ' . $getHist->id . ' : Already processed.',
                'type' => 'yellow',
                'status' => 'warning',
            ]));
        }
    }



    /**
     * Send the specified RPA history data to an external API.
     */
    public function sendApi(string $id)
    {
        $data = PortalRPAHist::findOrFail($id);

        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->post(env('RPA_URL'), [
                'json' => $data->prh_command,
            ]);
            $result = json_decode($response->getBody(), true);

            Redis::publish('portalv2', json_encode([
                'app' => 'rpa',
                'message' => 'RPA Job ID ' . $data->id . ' : Processed successfully.',
                'type' => 'green',
                'status' => 'success',
                'data' => $result,
            ]));
        } catch (\Exception $e) {
            Redis::publish('portalv2', json_encode([
                'app' => 'rpa',
                'message' => 'RPA Job ID ' . $data->id . ' : Process failed.',
                'type' => 'red',
                'status' => 'error',
                'data' => $e->getMessage(),
            ]));
        }
    }
}
