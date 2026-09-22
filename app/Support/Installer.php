<?php

namespace App\Support;

class Installer
{
    /**
     * Core database groups that MUST be configured by the wizard.
     * Prefix maps to env keys, e.g. prefix DB_ => DB_CONNECTION, DB_HOST...
     */
    public static function coreDatabases(): array
    {
        return [
            'main' => [
                'label' => 'Main Portal',
                'prefix' => 'DB_',
                'default_connection' => 'sqlsrv',
                'default_database' => 'STX_PORTAL',
            ],
            'cd' => [
                'label' => 'Connection Dynamic (CD)',
                'prefix' => 'DB_CD_',
                'default_connection' => 'sqlsrv_conn_dyn',
                'default_database' => 'STX_PORTAL',
            ],
            'dms' => [
                'label' => 'DMS',
                'prefix' => 'DB_DMS_',
                'default_connection' => 'sqlsrv_dms',
                'default_database' => 'STX_DMS',
            ],
            'dms_old' => [
                'label' => 'DMS (Legacy)',
                'prefix' => 'DB_DMS_OLD_',
                'default_connection' => 'sqlsrv_dms_old',
                'default_database' => 'DMS',
            ],
            'cms' => [
                'label' => 'CMS',
                'prefix' => 'DB_CMS_',
                'default_connection' => 'sqlsrv_cms',
                'default_database' => 'STX_CMS',
            ],
            'mrs' => [
                'label' => 'MRS',
                'prefix' => 'DB_MRS_',
                'default_connection' => 'sqlsrv_mrs',
                'default_database' => 'STX_MRS',
            ],
            'ams' => [
                'label' => 'AMS',
                'prefix' => 'DB_AMS_',
                'default_connection' => 'sqlsrv_ams',
                'default_database' => 'STX_AMS',
            ],
        ];
    }

    public static function supportedDrivers(): array
    {
        return ['sqlsrv', 'mysql', 'pgsql', 'sqlite'];
    }

    public static function lockPath(): string
    {
        return storage_path('installed');
    }

    public static function isInstalled(): bool
    {
        return file_exists(static::lockPath());
    }

    /**
     * Server requirements check for the wizard Step 1.
     *
     * @return array{checks: array, passed: bool, warnings: bool}
     */
    public static function requirements(): array
    {
        $checks = [];

        $checks[] = [
            'label' => 'PHP >= 8.1 (Laravel 10)',
            'value' => PHP_VERSION,
            'passed' => version_compare(PHP_VERSION, '8.1.0', '>='),
            'critical' => true,
            'hint' => 'Upgrade PHP to 8.1 or newer.',
        ];

        $extensions = [
            'openssl' => false,
            'pdo' => false,
            'mbstring' => false,
            'tokenizer' => false,
            'xml' => false,
            'ctype' => false,
            'json' => false,
            'bcmath' => false,
            'fileinfo' => false,
        ];
        foreach ($extensions as $ext => $critical) {
            $loaded = extension_loaded($ext);
            $checks[] = [
                'label' => "PHP extension: {$ext}",
                'value' => $loaded ? 'loaded' : 'missing',
                'passed' => $loaded,
                'critical' => true,
                'hint' => "Install/enable the php-{$ext} extension.",
            ];
        }

        // DB drivers are warnings, not blockers — at least one is needed,
        // the wizard will warn again per selected driver.
        foreach (['pdo_mysql' => 'mysql', 'pdo_pgsql' => 'pgsql', 'pdo_sqlite' => 'sqlite', 'pdo_sqlsrv' => 'sqlsrv'] as $ext => $driver) {
            $loaded = extension_loaded($ext);
            $checks[] = [
                'label' => "Driver for '{$driver}' ({$ext})",
                'value' => $loaded ? 'loaded' : 'missing',
                'passed' => $loaded,
                'critical' => false,
                'hint' => $driver === 'sqlsrv'
                    ? 'Required only if you use SQL Server. See Microsoft ODBC Driver + pdo_sqlsrv.'
                    : "Required only if you use {$driver}.",
            ];
        }

        $paths = [
            'storage/' => storage_path(),
            'bootstrap/cache/' => base_path('bootstrap/cache'),
        ];
        foreach ($paths as $label => $path) {
            $writable = is_dir($path) && is_writable($path);
            $checks[] = [
                'label' => "Writable: {$label}",
                'value' => $path,
                'passed' => $writable,
                'critical' => true,
                'hint' => "Make {$path} writable by the web server user.",
            ];
        }

        $envExample = base_path('.env.example');
        $checks[] = [
            'label' => '.env.example present',
            'value' => $envExample,
            'passed' => is_readable($envExample),
            'critical' => true,
            'hint' => '.env.example is used as the template for the generated .env.',
        ];

        $envDirWritable = is_writable(base_path()) || (file_exists(base_path('.env')) && is_writable(base_path('.env')));
        $checks[] = [
            'label' => '.env writable (project root)',
            'value' => base_path('.env'),
            'passed' => $envDirWritable,
            'critical' => true,
            'hint' => 'The web server must be able to create/write the .env file.',
        ];

        $passed = true;
        $warnings = false;
        foreach ($checks as $check) {
            if (!$check['passed'] && $check['critical']) {
                $passed = false;
            }
            if (!$check['passed'] && !$check['critical']) {
                $warnings = true;
            }
        }

        return compact('checks', 'passed', 'warnings');
    }

    /**
     * Test a single database connection with raw PDO (no Laravel config needed).
     *
     * @return array{ok: bool, message: string}
     */
    public static function testConnection(string $driver, ?string $host, ?string $port, ?string $database, ?string $username, ?string $password): array
    {
        $driver = strtolower(trim($driver));

        try {
            switch ($driver) {
                case 'mysql':
                    $port = $port ?: '3306';
                    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
                    $pdo = new \PDO($dsn, $username, $password, [\PDO::ATTR_TIMEOUT => 5]);
                    break;
                case 'pgsql':
                    $port = $port ?: '5432';
                    $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
                    $pdo = new \PDO($dsn, $username, $password, [\PDO::ATTR_TIMEOUT => 5]);
                    break;
                case 'sqlsrv':
                    if (!extension_loaded('pdo_sqlsrv')) {
                        return ['ok' => false, 'message' => 'pdo_sqlsrv extension is not installed on this server.'];
                    }
                    $port = $port ?: '1433';
                    // Host may already contain instance (HOST\INSTANCE) — keep as-is.
                    $dsn = "sqlsrv:Server={$host},{$port};Database={$database};LoginTimeout=5";
                    $pdo = new \PDO($dsn, $username, $password);
                    break;
                case 'sqlite':
                    if (!extension_loaded('pdo_sqlite')) {
                        return ['ok' => false, 'message' => 'pdo_sqlite extension is not installed.'];
                    }
                    $path = $database ?: database_path('database.sqlite');
                    $pdo = new \PDO("sqlite:{$path}");
                    break;
                default:
                    return ['ok' => false, 'message' => "Unsupported driver '{$driver}'."];
            }

            $pdo->query('SELECT 1');

            return ['ok' => true, 'message' => 'Connection successful.'];
        } catch (\PDOException $e) {
            return ['ok' => false, 'message' => 'Connection failed: '.$e->getMessage()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Connection failed: '.$e->getMessage()];
        }
    }

    /**
     * Write .env from .env.example template, overriding with $values.
     * Missing keys are appended. A timestamped backup of an existing
     * .env is kept next to it.
     */
    public static function writeEnv(array $values): void
    {
        $basePath = base_path();
        $example = $basePath.DIRECTORY_SEPARATOR.'.env.example';
        $target = $basePath.DIRECTORY_SEPARATOR.'.env';

        if (!is_readable($example)) {
            throw new \RuntimeException('.env.example not found or not readable.');
        }

        if (file_exists($target)) {
            @copy($target, $target.'.backup-'.date('Ymd-His'));
        }

        $lines = file($example, FILE_IGNORE_NEW_LINES);
        $handled = [];
        $out = [];

        foreach ($lines as $line) {
            if (preg_match('/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=/', $line, $m)) {
                $key = $m[1];
                if (array_key_exists($key, $values)) {
                    $out[] = $key.'='.static::escapeEnvValue((string) $values[$key]);
                    $handled[$key] = true;
                    continue;
                }
                $handled[$key] = true;
            }
            $out[] = $line;
        }

        foreach ($values as $key => $value) {
            if (!isset($handled[$key])) {
                $out[] = $key.'='.static::escapeEnvValue((string) $value);
            }
        }

        $written = @file_put_contents($target, implode("\n", $out)."\n", LOCK_EX);
        if ($written === false) {
            throw new \RuntimeException('Unable to write .env file. Check directory permissions.');
        }
    }

    protected static function escapeEnvValue(string $value): string
    {
        // Quote values containing spaces, # or quotes to keep dotenv parsing safe.
        if ($value === '' || preg_match('/[\s#"\'`$\\\\]/', $value)) {
            return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
        }

        return $value;
    }

    public static function markInstalled(array $meta = []): void
    {
        @file_put_contents(
            static::lockPath(),
            json_encode(array_merge([
                'installed_at' => date('c'),
                'app_url' => config('app.url'),
            ], $meta), JSON_PRETTY_PRINT),
            LOCK_EX
        );
    }
}
