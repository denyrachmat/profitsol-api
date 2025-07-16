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
use App\Models\PORTAL\PortalApp;
use Redis;
class InstallStatamicProject extends Command
{
    use GencodeTraits;
    protected $signature = 'statamic:install-project {id} {projectName} {username}';
    protected $description = 'Install a new Statamic project via CLI';

    public function handle()
    {
        Redis::publish('portalv2', json_encode([
            'app' => 'domain',
            'message' => 'Domain CMS installation started.',
            'type' => 'yellow',
            'status' => 'warning',
        ]));

        $id = $this->argument('id');
        $projectName = $this->argument('projectName');
        $username = $this->argument('username');

        $domain = PortalDomain::find($id);
        if (!$domain) {
            $this->error("Domain with ID $id not found.");
            return 1;
        }

        $parentPath = base_path("statamic-projects/{$domain->pd_name}");
        $publicPath = "{$parentPath}/public"; // The target public folder of the Statamic project
        $symlinkPath = public_path("statamic-projects/{$domain->pd_name}"); // The symlink location in main Laravel's public folder

        if (!file_exists($parentPath)) {
            mkdir($parentPath, 0755, true);
        }

        $phpPath = 'D:\laragon\bin\php\php-8.2.13\php.exe';
        $statamicPath = 'C:\\Users\\deny-rachmat\\AppData\\Roaming\\Composer\\vendor\\bin\\statamic';
        $composerPath = 'C:\ProgramData\ComposerSetup\bin\composer.bat';

        try {
            if (
                !$this->isGencodeExists('CMS_INSTALLED', [
                    'pgm_value' => (string) $id
                ])
            ) {
                // 1. Create new Statamic project
                $this->info("Creating Statamic project...");
                $process = new Process([
                    'cmd.exe',
                    '/c',
                    $composerPath,
                    'create-project',
                    'statamic/statamic',
                    $domain->pd_name,
                    '--quiet',
                    '--no-interaction'
                ], base_path('statamic-projects'));

                $process->setTimeout(null);
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

            // 2. Create symlink to public folder
            $this->info("Creating symlink to public folder...");
            if (file_exists($symlinkPath)) {
                $this->warn("Symlink already exists at {$symlinkPath}");
            } else {
                try {
                    // For Windows
                    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                        $this->createWindowsSymlink($publicPath, $symlinkPath, $id, $projectName, $username);
                    }
                    // For Linux/Mac
                    else {
                        symlink($publicPath, $symlinkPath);
                    }
                    $this->info("Symlink created successfully at {$symlinkPath}");
                } catch (\Exception $e) {
                    $this->error("Failed to create symlink: " . $e->getMessage());
                    Log::error("Symlink creation failed: " . $e->getMessage());
                    return 1;
                }
            }

            // 3. Run composer install
            $composerInstall = new Process([
                $composerPath,
                'install',
                '--no-interaction'
            ], $parentPath);

            $composerInstall->setTimeout(300);
            $composerInstall->mustRun();

            if (
                !$this->isGencodeExists('CMS_INSTALLED', [
                    'pgm_value' => (string) $id,
                    'pgm_value2' => 'setup_admin_done'
                ])
            ) {
                // 4. Install admin user manually
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

            // 5. Configure .env & generate application key
            // $envContent = <<<TEXT
            // APP_NAME="{$projectName}"
            // APP_URL=http://{$projectName}.test
            // TEXT;

            // file_put_contents("{$parentPath}/.env", $envContent);
            $this->configureEnvironment($parentPath, $projectName, "http://192.168.100.32/statamic-projects/{$domain->pd_name}");

            $this->info("Statamic project created at: {$parentPath}");
            $this->info("Accessible via: http://192.168.100.32/statamic-projects/{$domain->pd_name}");


            Redis::publish('portalv2', json_encode([
                'app' => 'domain',
                'message' => 'Domain CMS installation successfull !!',
                'type' => 'green',
                'status' => 'success',
            ]));
            return 0;

        } catch (\Exception $e) {
            Log::error("Failed to install Statamic project: " . $e->getMessage());
            $this->error("Error: " . $e->getMessage());

            PortalGencode::updateOrCreate(
                [
                    'pgm_code' => 'CMS_INSTALLED',
                    'pgm_value' => $id
                ],
                [
                    'pgm_code' => 'CMS_INSTALLED',
                    'pgm_value' => $id,
                    'pgm_value2' => 'setup_failed',
                    'pgm_value3' => $username,
                    'pgm_desc' => $projectName,
                    'pgm_desc3' => $e->getMessage(),
                ]
            );

            Redis::publish('portalv2', json_encode([
                'app' => 'domain',
                'message' => 'Domain CMS installation failed !!',
                'type' => 'red',
                'status' => 'error',
            ]));
            return 1;
        }
    }

    /**
     * Create symlink on Windows
     */
    protected function createWindowsSymlink($target, $link, $id, $projectName, $username)
    {
        $parentPublic = public_path("statamic-projects");
        if (!file_exists($parentPublic)) {
            mkdir($parentPublic, 0755, true);
        }
        // Check if we have permissions to create symlinks
        if (!function_exists('symlink')) {
            // Fallback to mklink command

            PortalGencode::updateOrCreate(
                [
                    'pgm_code' => 'CMS_INSTALLED',
                    'pgm_value' => $id
                ],
                [
                    'pgm_code' => 'CMS_INSTALLED',
                    'pgm_value' => $id,
                    'pgm_value2' => 'start_symlink_creation_using_mklink',
                    'pgm_desc' => $projectName,
                ]
            );

            $command = "mklink /D " . escapeshellarg($link) . " " . escapeshellarg($target);
            $process = new Process(explode(' ', $command));
            $process->run();

            if (!$process->isSuccessful()) {
                PortalGencode::updateOrCreate(
                    [
                        'pgm_code' => 'CMS_INSTALLED',
                        'pgm_value' => $id
                    ],
                    [
                        'pgm_code' => 'CMS_INSTALLED',
                        'pgm_value' => $id,
                        'pgm_value2' => 'setup_failed',
                        'pgm_value3' => $username,
                        'pgm_desc' => $projectName,
                        'pgm_desc3' => "Failed to create symlink: " . $process->getErrorOutput(),
                    ]
                );
                throw new \RuntimeException("Failed to create symlink: " . $process->getErrorOutput());
            }
        } else {
            PortalGencode::updateOrCreate(
                [
                    'pgm_code' => 'CMS_INSTALLED',
                    'pgm_value' => $id
                ],
                [
                    'pgm_code' => 'CMS_INSTALLED',
                    'pgm_value' => $id,
                    'pgm_value2' => 'start_symlink_creation_using_symlink_function',
                    'pgm_desc' => $projectName,
                    'pgm_desc2' => $target,
                    'pgm_desc3' => $link,
                ]
            );
            // Use PHP's symlink function if available
            symlink($target, $link);
        }
    }

    protected function configureEnvironment($path, $projectName, $appUrl)
    {
        $envFile = "{$path}/.env";

        // 1. Create .env if doesn't exist
        if (!File::exists($envFile)) {
            File::copy("{$path}/.env.example", $envFile);
        }

        // 2. Update specific values without replacing entire file
        $envContents = File::get($envFile);

        $updates = [
            'APP_NAME' => "\"{$projectName}\"",
            'APP_URL' => $appUrl,
            'APP_ENV' => 'local',
            'DB_DATABASE' => 'statamic_' . Str::slug($projectName),
            'ASSET_URL' => "/statamic-projects/{$projectName}",
        ];

        foreach ($updates as $key => $value) {
            $envContents = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $envContents
            );
        }

        File::put($envFile, $envContents);

        // 3. Generate application key
        $this->info("Generating application key...");
        $process = new Process([
            'php',
            'artisan',
            'key:generate'
        ], $path);

        $process->setTimeout(60);
        $process->mustRun();

        // 4. Verify key was generated
        $envContents = File::get($envFile);
        if (!Str::contains($envContents, 'APP_KEY=base64:')) {
            throw new \Exception('Failed to generate application key');
        }
    }
}
