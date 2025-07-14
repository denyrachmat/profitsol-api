<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;
use App\Models\PORTAL\PortalDomain;
use App\Traits\PORTAL\GencodeTraits;
use App\Models\PORTAL\PortalGencode;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class InstallStatamicProject extends Command
{
    use GencodeTraits;
    protected $signature = 'statamic:install-project {id} {projectName} {username}';
    protected $description = 'Install a new Statamic project via CLI';

    public function handle()
    {
        $id = $this->argument('id');
        $projectName = $this->argument('projectName');
        $username = $this->argument('username');

        $domain = PortalDomain::find($id);
        if (!$domain) {
            $this->error("Domain with ID $id not found.");
            return 1;
        }

        $parentPath = base_path("statamic-projects/{$domain->pd_name}");
        if (!file_exists($parentPath)) {
            mkdir($parentPath, 0755, true);
        }

        $phpPath = 'D:\laragon\bin\php\php-8.2.13\php.exe'; // Sesuaikan dengan path PHP CLI kamu
        $statamicPath = 'C:\\Users\\deny-rachmat\\AppData\\Roaming\\Composer\\vendor\\bin\\statamic'; // Path ke statamic binary

        $composerPath = 'C:\ProgramData\ComposerSetup\bin\composer.bat'; // Jika kamu pakai composer.phar, sertakan juga

        try {
            if (
                !$this->isGencodeExists('CMS_INSTALLED', [
                    'pgm_value' => (string) $id,
                    'pgm_value2' => 'installed'
                ])
            ) {
                // 1. Create new Statamic project
                $this->info("Creating Statamic project...");
                $process = new Process([
                    $composerPath,
                    'create-project',
                    'statamic/statamic',
                    $domain->pd_name,
                    '--quiet',
                    '--no-interaction'
                ], base_path('statamic-projects'));

                $process->setTimeout(null); // atau null untuk tanpa batas
                $process->mustRun();

                if ($process->isSuccessful()) {
                    PortalGencode::updateOrCreate(
                        [
                            'pgm_code' => 'CMS_INSTALLED',
                            'pgm_value' => $id
                        ],
                        [
                            'pgm_code' => 'CMS_INSTALLED',
                            'pgm_value' => $id,
                            'pgm_value2' => 'installed',
                            'pgm_desc' => $projectName,
                        ]
                    );
                }
            }

            $composerInstall = new Process([
                $composerPath,
                'install',
                '--no-interaction'
            ], $parentPath); // <- Di dalam direktori proyek

            $composerInstall->setTimeout(300);
            $composerInstall->mustRun();

            if (
                !$this->isGencodeExists('CMS_INSTALLED', [
                    'pgm_value' => (string) $id,
                    'pgm_value2' => 'setup_admin_done'
                ])
            ) {
                // 2. Install admin user manually (for Statamic Free)
                $this->info("Setting up admin user manually...");

                $hashedUsername = Str::slug($username);
                $passwordHash = bcrypt($username);

                File::ensureDirectoryExists("{$parentPath}/users");

                $userYaml = <<<YAML
                email: {$username}
                password: "{$passwordHash}"
                roles:
                - super
                YAML;

                File::put("{$parentPath}/users/{$hashedUsername}.yaml", $userYaml);

                PortalGencode::updateOrCreate(
                    [
                        'pgm_code' => 'CMS_INSTALLED',
                        'pgm_value' => $id
                    ],
                    [
                        'pgm_code' => 'CMS_INSTALLED',
                        'pgm_value' => $id,
                        'pgm_value2' => 'setup_admin_done',
                        'pgm_value3' => $username,
                        'pgm_desc' => $projectName,
                        'pgm_desc2' => $passwordHash,
                    ]
                );
            }

            // 3. Configure .env
            $envContent = <<<TEXT
            APP_NAME="{$projectName}"
            APP_URL=http://{$projectName}.test
            TEXT;

            file_put_contents("{$parentPath}/.env", $envContent);

            $this->info("Statamic project created at: {$parentPath}");
            return 0;

        } catch (\Exception $e) {
            Log::error("Failed to install Statamic project: " . $e->getMessage());
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}
