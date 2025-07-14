<?php

namespace App\Jobs\PORTAL;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use App\Models\PORTAL\PortalGencode;

class StatamicGenerateQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $id, $projectName, $username;
    public function __construct($id, $projectName, $username)
    {
        $this->id = $id;
        $this->projectName = $projectName;
        $this->username = $username;
    }

    public function handle()
    {
        PortalGencode::updateOrCreate(
            [
                'pgm_code' => 'CMS_INSTALLED',
                'pgm_value' => $this->id,
            ],
            [
                'pgm_code' => 'CMS_INSTALLED',
                'pgm_value' => $this->id,
                'pgm_value2' => 'setup_started',
                'pgm_value3' => $this->username,
                'pgm_desc' => $this->projectName,
                'pgm_desc2' => '',
            ]
        );

        Artisan::call('statamic:install-project', [
            'id' => $this->id,
            'projectName' => $this->projectName,
            'username' => $this->username,
        ]);
    }
}
