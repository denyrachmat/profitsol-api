<?php

namespace App\Console\Commands;

use App\Support\Installer;
use Illuminate\Console\Command;

class MarkInstalled extends Command
{
    protected $signature = 'app:mark-installed';
    protected $description = 'Create the storage/installed lock so the web installer is disabled (for servers installed before the wizard existed).';

    public function handle(): int
    {
        if (!file_exists(base_path('.env'))) {
            $this->error('.env not found. Run the web installer at /install first.');
            return self::FAILURE;
        }

        Installer::markInstalled();
        $this->info('Done. Installer locked via storage/installed.');

        return self::SUCCESS;
    }
}
