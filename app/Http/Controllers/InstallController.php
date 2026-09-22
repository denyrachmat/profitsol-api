<?php

namespace App\Http\Controllers;

use App\Support\Installer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

class InstallController extends Controller
{
    /** Step 1: requirements check (WordPress-style welcome). */
    public function index()
    {
        $requirements = Installer::requirements();
        $alreadyHasEnv = file_exists(base_path('.env'));

        return view('install.requirements', [
            'checks' => $requirements['checks'],
            'canContinue' => $requirements['passed'],
            'hasWarnings' => $requirements['warnings'],
            'alreadyHasEnv' => $alreadyHasEnv,
            'step' => 1,
        ]);
    }

    /** Step 2: environment + core databases form. */
    public function database()
    {
        $requirements = Installer::requirements();
        if (!$requirements['passed']) {
            return redirect()->route('install.index')
                ->with('error', 'Please fix the failed requirements before continuing.');
        }

        $env = $this->currentEnvValues();

        return view('install.database', [
            'step' => 2,
            'drivers' => Installer::supportedDrivers(),
            'groups' => Installer::coreDatabases(),
            'env' => $env,
        ]);
    }

    /** AJAX: test a single DB connection without saving anything. */
    public function testConnection(Request $request)
    {
        $data = $request->validate([
            'driver' => 'required|string',
            'host' => 'nullable|string|max:255',
            'port' => 'nullable|string|max:10',
            'database' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
        ]);

        $result = Installer::testConnection(
            $data['driver'],
            $data['host'] ?? null,
            $data['port'] ?? null,
            $data['database'] ?? null,
            $data['username'] ?? null,
            $data['password'] ?? null
        );

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /** Step 2 submit: validate, test every core DB, write .env, generate key. */
    public function store(Request $request)
    {
        $groups = Installer::coreDatabases();

        $rules = [
            'app_name' => 'required|string|max:100',
            'app_url' => 'required|url|max:255',
            'app_env' => 'required|in:local,production',
        ];
        foreach ($groups as $key => $group) {
            $rules["db.{$key}.connection"] = 'required|string|in:sqlsrv,mysql,pgsql,sqlite';
            $rules["db.{$key}.host"] = 'required_unless:db.'.$key.'.connection,sqlite|string|max:255|nullable';
            $rules["db.{$key}.port"] = 'nullable|string|max:10';
            $rules["db.{$key}.database"] = 'required|string|max:255';
            $rules["db.{$key}.username"] = 'nullable|string|max:255';
            $rules["db.{$key}.password"] = 'nullable|string|max:255';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->route('install.database')
                ->withErrors($validator)
                ->withInput();
        }
        $data = $validator->validated();

        // Test every core connection before writing anything.
        $failures = [];
        foreach ($groups as $key => $group) {
            $cfg = $data['db'][$key];
            $result = Installer::testConnection(
                $cfg['connection'],
                $cfg['host'] ?? null,
                $cfg['port'] ?? null,
                $cfg['database'],
                $cfg['username'] ?? null,
                $cfg['password'] ?? null
            );
            if (!$result['ok']) {
                $failures["db.{$key}.database"] = "{$group['label']}: {$result['message']}";
            }
        }

        if (!empty($failures)) {
            return redirect()->route('install.database')
                ->withErrors($failures)
                ->withInput()
                ->with('error', 'One or more database connections failed. Fix them and try again.');
        }

        // Build .env values: app + every core DB group.
        $values = [
            'APP_NAME' => $data['app_name'],
            'APP_ENV' => $data['app_env'],
            'APP_URL' => rtrim($data['app_url'], '/'),
            'APP_DEBUG' => $data['app_env'] === 'local' ? 'true' : 'false',
        ];
        $defaultPorts = ['sqlsrv' => '1433', 'mysql' => '3306', 'pgsql' => '5432', 'sqlite' => ''];
        foreach ($groups as $key => $group) {
            $cfg = $data['db'][$key];
            $prefix = $group['prefix'];
            $values[$prefix.'CONNECTION'] = $cfg['connection'];
            $values[$prefix.'HOST'] = $cfg['host'] ?? '';
            $values[$prefix.'PORT'] = ($cfg['port'] !== null && $cfg['port'] !== '')
                ? $cfg['port']
                : ($defaultPorts[$cfg['connection']] ?? '');
            $values[$prefix.'DATABASE'] = $cfg['database'];
            $values[$prefix.'USERNAME'] = $cfg['username'] ?? '';
            $values[$prefix.'PASSWORD'] = $cfg['password'] ?? '';
        }

        try {
            Installer::writeEnv($values);
        } catch (\Throwable $e) {
            return redirect()->route('install.database')
                ->withInput()
                ->with('error', $e->getMessage());
        }

        // Regenerate key into the freshly written .env.
        try {
            Artisan::call('key:generate', ['--force' => true]);
        } catch (\Throwable $e) {
            return redirect()->route('install.database')
                ->withInput()
                ->with('error', '.env was written but APP_KEY generation failed: '.$e->getMessage());
        }

        return redirect()->route('install.run')
            ->with('success', '.env created and APP_KEY generated. Now run the final installation step.');
    }

    /** Step 3: run migrations + storage link. */
    public function run()
    {
        if (!file_exists(base_path('.env'))) {
            return redirect()->route('install.database')
                ->with('error', '.env not found. Complete step 2 first.');
        }

        return view('install.run', ['step' => 3]);
    }

    public function execute(Request $request)
    {
        $log = [];

        try {
            Artisan::call('migrate', ['--force' => true]);
            $log[] = '--- migrate --force ---';
            $log[] = Artisan::output();
        } catch (\Throwable $e) {
            return redirect()->route('install.run')
                ->with('error', 'Migration failed: '.$e->getMessage())
                ->with('install_log', $log);
        }

        try {
            Artisan::call('storage:link');
            $log[] = '--- storage:link ---';
            $log[] = Artisan::output();
        } catch (\Throwable $e) {
            // Non-fatal on shared hosting / Windows — keep going.
            $log[] = 'storage:link warning: '.$e->getMessage();
        }

        Installer::markInstalled(['app_url' => env('APP_URL')]);

        return redirect()->route('install.complete')->with('install_log', $log);
    }

    /** Step 4: done. */
    public function complete()
    {
        if (!Installer::isInstalled()) {
            return redirect()->route('install.index');
        }

        return view('install.complete', [
            'step' => 4,
            'log' => session('install_log', []),
            'appUrl' => env('APP_URL', config('app.url')),
        ]);
    }

    /**
     * Prefill the form: current .env values if present,
     * otherwise sensible defaults per core group.
     */
    protected function currentEnvValues(): array
    {
        $env = [];
        $envFile = base_path('.env');
        $source = file_exists($envFile) ? $envFile : base_path('.env.example');

        if (is_readable($source)) {
            foreach (file($source, FILE_IGNORE_NEW_LINES) as $line) {
                if (preg_match('/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)\s*$/', $line, $m)) {
                    $env[$m[1]] = trim($m[2], " \t\"'");
                }
            }
        }

        $env['APP_NAME'] = $env['APP_NAME'] ?? 'ProfitSol';
        $env['APP_URL'] = $env['APP_URL'] ?? request()->getSchemeAndHttpHost();
        $env['APP_ENV'] = $env['APP_ENV'] ?? 'production';

        foreach (Installer::coreDatabases() as $group) {
            $p = $group['prefix'];
            $env[$p.'CONNECTION'] = $env[$p.'CONNECTION'] ?? $group['default_connection'];
            $env[$p.'HOST'] = $env[$p.'HOST'] ?? ($env['DB_HOST'] ?? '127.0.0.1');
            $env[$p.'PORT'] = $env[$p.'PORT'] ?? ($env['DB_PORT'] ?? '1433');
            $env[$p.'DATABASE'] = $env[$p.'DATABASE'] ?? $group['default_database'];
            $env[$p.'USERNAME'] = $env[$p.'USERNAME'] ?? ($env['DB_USERNAME'] ?? 'sa');
            // Never prefill passwords from example; leave empty unless .env already has one.
            if (!file_exists($envFile)) {
                $env[$p.'PASSWORD'] = '';
            } else {
                $env[$p.'PASSWORD'] = $env[$p.'PASSWORD'] ?? '';
            }
        }

        return $env;
    }
}
