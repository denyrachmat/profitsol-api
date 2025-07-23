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
        try {

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
                    'pgm_parent' => 1,
                ]
            );

            // Artisan::call('statamic:install-project', [
            //     'id' => $this->id,
            //     'projectName' => $this->projectName,
            //     'username' => $this->username,
            // ]);
            $exitCode = Artisan::call('statamic:install-project', [
                'id' => $this->id,
                'projectName' => $this->projectName,
                'username' => $this->username,
            ]);

            if ($exitCode !== 0) {
                throw new \RuntimeException("Installation failed with exit code: {$exitCode}");
            }

            // Optionally get and log the command output
            $output = Artisan::output();
            \Log::info("Statamic installation output:", ['output' => $output]);

        } catch (\Exception $e) {
            \Log::error("Statamic installation failed", [
                'project' => $this->projectName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->fail($e);
        }
    }
}
