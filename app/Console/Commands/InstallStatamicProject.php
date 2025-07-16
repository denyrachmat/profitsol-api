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
use Symfony\Component\Process\Exception\ProcessFailedException;
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
            // 1. Create new Statamic project
            $this->info("Creating Statamic project...");

            $process = new Process([
                $composerPath,
                'create-project',
                'statamic/statamic',
                $domain->pd_name,
                '--quiet',
                '--no-scripts',
                '--no-dev',
                '--prefer-dist',
                '--no-interaction',
                '--no-secure-http' // Add this flag
            ], base_path('statamic-projects'));

            $process->setTimeout(3600); // Set a timeout of 1 hour
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
                        'pgm_value2' => 'project_installed',
                        'pgm_desc' => $projectName,
                    ]
                );

                Redis::publish('portalv2', json_encode([
                    'app' => 'domain',
                    'message' => 'project installed',
                    'type' => 'yellow',
                    'status' => 'warning',
                ]));
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

                    Redis::publish('portalv2', json_encode([
                        'app' => 'domain',
                        'message' => 'Symlink created successfully',
                        'type' => 'yellow',
                        'status' => 'warning',
                    ]));
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
                        'pgm_desc3' => env('STATAMIC_ROOT').'STX_PORTAL/cp',
                    ]
                );

                Redis::publish('portalv2', json_encode([
                    'app' => 'domain',
                    'message' => 'Admin Project setup done',
                    'type' => 'yellow',
                    'status' => 'warning',
                ]));
            }

            // 5. Configure .env & generate application key

            // file_put_contents("{$parentPath}/.env", $envContent);
            $this->configureEnvironment($parentPath, $projectName, env('STATAMIC_ROOT')."{$domain->pd_name}", $domain);

            $this->info("Statamic project created at: {$parentPath}");
            $this->info("Accessible via: " . env('STATAMIC_ROOT') . "{$domain->pd_name}");


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

    protected function configureEnvironment($path, $projectName, $appUrl, $domain)
    {
        $envFile = "{$path}/.env";

        // 1. Create .env if doesn't exist
        if (!File::exists($envFile)) {
            File::copy("{$path}/.env.example", $envFile);
        }

        Redis::publish('portalv2', json_encode([
            'app' => 'domain',
            'message' => '.env file created / found',
            'type' => 'yellow',
            'status' => 'warning',
        ]));

        // 2. Update specific values without replacing entire file
        $envContents = File::get($envFile);

        $this->info("Creating database for Statamic project...");

        $dbName = strtoupper('STMC_' . Str::slug($projectName));
        $dbUser = env('DB_USERNAME', 'root');
        $dbPass = env('DB_PASSWORD', '');
        $dbHost = env('DB_HOST', '127.0.0.1');
        $dbPort = env('DB_PORT', '3306');


        $updates = [
            'APP_NAME' => "\"{$projectName}\"",
            'APP_URL' => $appUrl,
            'APP_ENV' => 'local',
            'APP_KEY' => 'base64:' . base64_encode(random_bytes(32)),
            'ASSET_URL' => "/statamic-projects/{$projectName}",
            'DB_CONNECTION' => $domain->pd_dbtype,
            '# DB_HOST' => $domain->pd_host ?: $dbHost,
            '# DB_PORT' => $domain->pd_port ?: $dbPort,
            '# DB_DATABASE' => 'STMC_' . Str::slug($projectName),
            '# DB_PASSWORD' => $domain->pd_password ?: $dbPass,
            '# DB_USERNAME' => $domain->pd_username ?: $dbUser,
            'SESSION_DOMAIN' => $appUrl,
            'SESSION_DRIVER' => 'database',
            'COOKIE_DOMAIN' => $appUrl
        ];

        try {
            // Build DSN string based on database type
            $dsn = '';
            if ($domain->pd_dbtype === 'mysql') {
                $dsn = "mysql:host={$dbHost};port={$dbPort}";
            } elseif ($domain->pd_dbtype === 'pgsql') {
                $dsn = "pgsql:host={$dbHost};port={$dbPort}";
            } elseif ($domain->pd_dbtype === 'sqlsrv') {
                $dsn = "sqlsrv:Server={$dbHost}," . ($dbPort ?: '1433');
            } else {
                throw new \Exception("Unsupported database type: {$domain->pd_dbtype}");
            }

            $pdo = new \PDO($dsn, $dbUser, $dbPass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);

            if ($domain->pd_dbtype === 'mysql') {
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            } elseif ($domain->pd_dbtype === 'pgsql') {
                $pdo->exec("CREATE DATABASE IF NOT EXISTS \"{$dbName}\";");
            } elseif ($domain->pd_dbtype === 'sqlsrv') {
                $pdo->exec("IF NOT EXISTS (SELECT name FROM sys.databases WHERE name = N'{$dbName}') CREATE DATABASE [{$dbName}];");
            }

            $this->info("Database '{$dbName}' created or already exists.");

            Redis::publish('portalv2', json_encode([
                'app' => 'domain',
                'message' => 'Database created or already exists',
                'type' => 'yellow',
                'status' => 'warning',
            ]));
        } catch (\PDOException $e) {
            Log::error("Failed to create database: " . $e->getMessage());

            Redis::publish('portalv2', json_encode([
                'app' => 'domain',
                'message' => 'Database failed to create',
                'type' => 'yellow',
                'status' => 'warning',
            ]));
            throw new \Exception("Failed to create database: " . $e->getMessage());
        }

        foreach ($updates as $key => $value) {
            $envContents = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value} # updated by InstallStatamicProject",
                $envContents
            );
        }

        $envContents = preg_replace('/^# (DB_HOST|DB_PORT|DB_DATABASE|DB_PASSWORD|DB_USERNAME)=/m', '$1=', $envContents);

        File::put($envFile, $envContents);

        // 3. Generate application key
        $this->info("Generating application key...");
        $phpPath = 'D:\laragon\bin\php\php-8.2.13\php.exe';
        $artisanPath = "artisan";
        $process = new Process([
            $phpPath,
            $artisanPath,
            'key:generate'
        ], $path);

        $process->setTimeout(600);
        $process->mustRun();

        if (!$process->isSuccessful()) {
            Log::error("Failed to generate application key: " . $process->getErrorOutput());
        } else {
            Redis::publish('portalv2', json_encode([
                'app' => 'domain',
                'message' => 'Environment file updated and application key generated',
                'type' => 'yellow',
                'status' => 'warning',
            ]));
        }

        // 4. Verify key was generated
        $envContents = File::get($envFile);
        if (!Str::contains($envContents, 'APP_KEY=base64:')) {
            throw new \Exception("Failed to generate application key: " . $process->getOutput());
        }

        // $this->info("Running 'php artisan session:table'...");
        // $process = new Process([
        //     $phpPath,
        //     $artisanPath,
        //     'session:table'
        // ], $path);
        // $process->setTimeout(300);
        // $process->mustRun();

        // $this->info("Running 'php artisan migrate'...");
        // $process = new Process([
        //     $phpPath,
        //     $artisanPath,
        //     'migrate',
        //     '--force'
        // ], $path);
        // $process->setTimeout(600);
        // $process->mustRun();

        // Redis::publish('portalv2', json_encode([
        //     'app' => 'domain',
        //     'message' => 'Session table created and migrations run',
        //     'type' => 'yellow',
        //     'status' => 'warning',
        // ]));

        $this->info("Clearing Laravel caches...");
        $commands = [
            ['cache:clear'],
            ['view:clear'],
            ['config:clear'],
            // ['session:clear'],
        ];

        foreach ($commands as $cmd) {
            $process = new Process([
                $phpPath,
                $artisanPath,
                ...$cmd
            ], $path);
            $process->setTimeout(120);
            $process->mustRun();
            $this->info("Ran 'php artisan {$cmd[0]}'");
        }

        Redis::publish('portalv2', json_encode([
            'app' => 'domain',
            'message' => 'Laravel caches cleared',
            'type' => 'yellow',
            'status' => 'warning',
        ]));
    }
}
