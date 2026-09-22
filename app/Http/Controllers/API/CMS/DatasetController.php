<?php

namespace App\Http\Controllers\API\CMS;

use App\Http\Controllers\API\PORTAL\BaseController;
use App\Models\CMS\DatasetMstr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class DatasetController extends BaseController
{
    private const MAX_ROWS = 1000;
    private const TEST_ROWS = 50;

    /**
     * List all datasets (management screen).
     */
    public function index()
    {
        $data = DatasetMstr::orderBy('cds_name')->get();
        return $this->handleResponse($data, 'Data Found !');
    }

    /**
     * Display the specified dataset.
     */
    public function show($id)
    {
        $data = DatasetMstr::find($id);
        if (!$data) {
            return $this->handleError('Dataset not found', 404);
        }
        return $this->handleResponse($data, 'Data Found !');
    }

    /**
     * Create or update a dataset (upsert by idRef, same pattern as cms/forms).
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'code' => 'required|string|max:100|regex:/^[a-z0-9][a-z0-9\-_]*$/',
            'type' => 'required|in:sql,api,api_ext',
        ]);

        $idRef = $request->idRef;

        $clash = DatasetMstr::where('cds_code', $request->code)
            ->when($idRef, fn ($q) => $q->where('id', '<>', $idRef))
            ->first();
        if ($clash) {
            return $this->handleError('Dataset code is already used by another dataset.', 422);
        }

        // Validate the definition before persisting so broken datasets never
        // reach a dashboard.
        try {
            $this->validateDefinition($request);
        } catch (\RuntimeException $e) {
            return $this->handleError($e->getMessage(), 422);
        }

        $isApi = in_array($request->type, ['api', 'api_ext'], true);

        try {
            $headers = $isApi ? $this->normalizeHeaders($request->input('headers')) : [];
        } catch (\RuntimeException $e) {
            return $this->handleError($e->getMessage(), 422);
        }

        $payload = [
            'p_u_username' => $request->header('username'),
            'cds_code' => $request->code,
            'cds_name' => $request->name,
            'cds_desc' => $request->desc,
            'cds_type' => $request->type,
            'cds_connection' => $request->type === 'sql' ? ($request->input('connection') ?: 'sqlsrv_cms') : null,
            'cds_query' => $request->type === 'sql' ? $request->input('query') : null,
            'cds_endpoint' => $isApi ? $request->input('endpoint') : null,
            'cds_method' => $isApi ? (strtolower($request->input('method') ?? 'get') === 'post' ? 'post' : 'get') : 'get',
            'cds_payload' => $isApi ? $request->input('payload') : null,
            'cds_headers' => $isApi ? json_encode($headers) : null,
            'cds_params_schema' => json_encode($this->normalizeParamsSchema($request->paramsSchema)),
            'cds_cache_ttl' => max(0, (int) $request->input('cacheTtl', 300)),
            'cds_roles' => json_encode($this->normalizeRoles($request->roles)),
            'cds_status' => $request->input('status', 'active'),
        ];

        $dataset = DatasetMstr::updateOrCreate(['id' => $idRef], $payload);

        return $this->handleResponse($dataset, 'Dataset saved successfully');
    }

    /**
     * Soft delete a dataset.
     */
    public function destroy($id)
    {
        $dataset = DatasetMstr::find($id);
        if (!$dataset) {
            return $this->handleError('Dataset not found', 404);
        }
        $dataset->delete();
        return $this->handleResponse([], 'Dataset deleted successfully');
    }

    /**
     * Whitelisted DB connection names for SQL datasets (no credentials, just keys).
     */
    public function connections()
    {
        $names = collect(array_keys(config('database.connections')))
            ->filter(fn ($name) => str_starts_with($name, 'sqlsrv'))
            ->map(function ($name) {
                $cfg = config("database.connections.{$name}", []);
                $host = (string) ($cfg['host'] ?? '');
                $db = (string) ($cfg['database'] ?? '');
                // `localhost` / `forge` are the config defaults — a connection
                // still at those values has no real env config and would
                // silently fail, so the editor must not offer it.
                $configured = $host !== '' && $host !== 'localhost'
                    && $db !== '' && $db !== 'forge';

                return [
                    'name' => $name,
                    'host' => $host,
                    'database' => $db,
                    'configured' => $configured,
                ];
            })
            ->values()
            ->all();

        return $this->handleResponse($names, 'Data Found !');
    }

    /**
     * Run an ad-hoc (unsaved) dataset definition — the editor's Test button.
     * Always limited to TEST_ROWS and never cached.
     */
    public function test(Request $request)
    {
        try {
            $this->validateDefinition($request);
            $rows = $this->runDefinition(
                $request->type,
                $request->input('connection'),
                $request->input('query'),
                $request->input('endpoint'),
                $request->input('method'),
                $request->input('payload'),
                $this->normalizeHeaders($request->input('headers')),
                $this->normalizeParamsSchema($request->paramsSchema),
                (array) $request->input('params', []),
                self::TEST_ROWS
            );

            return $this->handleResponse([
                'rows' => $rows,
                'columns' => $this->extractColumns($rows),
                'truncated' => count($rows) >= self::TEST_ROWS,
            ], 'Query executed successfully');
        } catch (\Throwable $e) {
            return $this->handleError($this->safeErrorMessage($e), 422);
        }
    }

    /**
     * Execute a saved dataset by code with caching + role enforcement.
     * This is the only endpoint dashboards/widgets call at render time.
     */
    public function data(Request $request, $code)
    {
        $dataset = DatasetMstr::where('cds_code', $code)->first();
        if (!$dataset || $dataset->cds_status !== 'active') {
            return $this->handleError('Dataset not found', 404);
        }

        $denied = $this->rolesDenied($dataset->cds_roles, trim((string) $request->header('roleid')));
        if ($denied) {
            return $denied;
        }

        $schema = $this->decodeJson($dataset->cds_params_schema) ?: [];
        $params = $this->sanitizeParams((array) $request->input('params', []), $schema);
        $headers = $this->decodeJson($dataset->cds_headers) ?: [];

        $ttl = max(0, (int) $dataset->cds_cache_ttl);
        $cacheKey = 'cms.dataset.' . $dataset->id . '.' . sha1(json_encode($params));

        try {
            $run = function () use ($dataset, $schema, $params, $headers) {
                return $this->runDefinition(
                    $dataset->cds_type,
                    $dataset->cds_connection,
                    $dataset->cds_query,
                    $dataset->cds_endpoint,
                    $dataset->cds_method,
                    $dataset->cds_payload,
                    $headers,
                    $schema,
                    $params,
                    self::MAX_ROWS
                );
            };

            $rows = $ttl > 0
                ? Cache::remember($cacheKey, now()->addSeconds($ttl), $run)
                : $run();

            return $this->handleResponse([
                'rows' => $rows,
                'columns' => $this->extractColumns($rows),
                'cached' => $ttl > 0,
            ], 'Data Found !');
        } catch (\Throwable $e) {
            return $this->handleError($this->safeErrorMessage($e), 422);
        }
    }

    /* ------------------------------------------------------------------ */

    private function validateDefinition(Request $request): void
    {
        if ($request->type === 'sql') {
            $conn = $request->input('connection') ?: 'sqlsrv_cms';
            $this->assertValidConnection($conn);
            $this->assertReadOnlySql((string) $request->input('query'));
            return;
        }

        $endpoint = trim((string) $request->input('endpoint'));
        if ($endpoint === '') {
            throw new \RuntimeException('Endpoint is required.');
        }

        // Header names/values are only relevant for API datasets, but validate
        // them here so the editor's Test button catches mistakes too.
        $this->normalizeHeaders($request->input('headers'));

        if ($request->type === 'api_ext') {
            if (!preg_match('#^https?://#i', $endpoint)) {
                throw new \RuntimeException('External API URL must start with http:// or https://.');
            }
            if (filter_var($endpoint, FILTER_VALIDATE_URL) === false) {
                throw new \RuntimeException('External API URL is not a valid URL.');
            }
            return;
        }

        if (preg_match('#^https?://#i', $endpoint)) {
            throw new \RuntimeException('Full URLs are not allowed for Internal API — use the External API type instead.');
        }
    }

    private function assertValidConnection(string $conn): void
    {
        $valid = str_starts_with($conn, 'sqlsrv')
            && array_key_exists($conn, config('database.connections'));
        if (!$valid) {
            throw new \RuntimeException('Unknown database connection.');
        }
    }

    /**
     * Read-only guard: single SELECT/WITH statement, no write keywords,
     * comments stripped before checking so they cannot smuggle statements.
     */
    private function assertReadOnlySql(string $sql): void
    {
        if (trim($sql) === '') {
            throw new \RuntimeException('Query is required.');
        }

        $clean = preg_replace('/(--[^\r\n]*)|(\/\*.*?\*\/)/s', ' ', $sql);
        $clean = trim($clean);

        if (!preg_match('/^(select|with)\b/i', $clean)) {
            throw new \RuntimeException('Only SELECT queries are allowed.');
        }

        if (preg_match('/\b(insert|update|delete|drop|alter|create|truncate|merge|grant|revoke|exec|execute|xp_cmdshell|into)\b/i', $clean)) {
            throw new \RuntimeException('Query contains forbidden keywords.');
        }

        $noTrailing = rtrim(rtrim($clean), ';');
        if (str_contains($noTrailing, ';')) {
            throw new \RuntimeException('Only a single statement is allowed.');
        }
    }

    private function runDefinition($type, $connection, $query, $endpoint, $method, $payloadTpl, $headers, array $schema, array $params, int $limit): array
    {
        if ($type === 'api' || $type === 'api_ext') {
            return $this->runApiDataset($type, $endpoint, $method, $payloadTpl, $headers, $schema, $params, $limit);
        }

        return $this->runSqlDataset($connection, $query, $schema, $params, $limit);
    }

    private function runSqlDataset($connection, $query, array $schema, array $params, int $limit): array
    {
        $conn = $connection ?: 'sqlsrv_cms';
        $this->assertValidConnection($conn);
        $this->assertReadOnlySql((string) $query);

        // Declared params bind positionally (in schema order) to '?' markers.
        $bindings = [];
        foreach ($schema as $def) {
            if (empty($def['name'])) {
                continue;
            }
            $bindings[] = $params[$def['name']] ?? ($def['default'] ?? null);
        }

        // Cap rows BEFORE fetching. DB::select() materializes the whole
        // result set first, so PHP-side slicing alone still loads e.g. 137k
        // rows into memory and kills the worker (128M) with no log entry.
        // TOP-injection is the primary cap (deterministic); SET ROWCOUNT +
        // an early-break fetch loop are backstops for shapes TOP can't
        // rewrite (CTEs, UNIONs, ...).
        $cap = max(1, (int) $limit);
        $sql = $this->injectTop((string) $query, $cap);

        $db = DB::connection($conn);
        $db->statement('SET ROWCOUNT ' . $cap);
        try {
            $stmt = $db->getPdo()->prepare($sql);
            $stmt->execute(array_values($bindings));
            $rows = [];
            while (count($rows) < $cap && ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
                $rows[] = $this->cleanRow($row);
            }
            $stmt->closeCursor();
        } catch (\Illuminate\Database\QueryException | \PDOException $e) {
            throw $this->sqlExecutionError($conn, $e);
        } finally {
            try {
                $db->statement('SET ROWCOUNT 0');
            } catch (\Throwable $ignored) {
                // Never mask the real result/exception.
            }
        }

        return array_slice($rows, 0, $cap);
    }

    /**
     * Rewrites `SELECT [DISTINCT] ...` to `SELECT [DISTINCT] TOP n ...`.
     * Skipped when TOP is already present or the query isn't a plain SELECT
     * (e.g. WITH... CTEs — those rely on the ROWCOUNT + fetch-loop backstop).
     */
    private function injectTop(string $sql, int $cap): string
    {
        if (preg_match('/^\s*SELECT\s+(?:DISTINCT\s+)?TOP\b/i', $sql)) {
            return $sql;
        }
        if (preg_match('/^(\s*SELECT\s+(?:DISTINCT\s+)?)/i', $sql, $m)) {
            return $m[1] . 'TOP ' . $cap . ' ' . substr($sql, strlen($m[1]));
        }
        return $sql;
    }

    /**
     * Normalizes one result row so json_encode can never fail on it:
     * - DateTime (sqlsrv returns datetime columns as objects) -> string
     * - streams/resources (varbinary/text) -> read contents
     * - strings -> guaranteed valid UTF-8 (legacy DBs may store Shift-JIS or
     *   binary bytes in varchar columns; json_encode returns false on those
     *   and Laravel then throws Malformed UTF-8 -> HTTP 500)
     * - non-finite floats -> null
     */
    private function cleanRow(array $row): array
    {
        $out = [];
        foreach ($row as $key => $value) {
            $out[$key] = $this->cleanValue($value);
        }
        return $out;
    }

    private function cleanValue($value)
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $key => $item) {
                $out[$key] = $this->cleanValue($item);
            }
            return $out;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_resource($value)) {
            $data = stream_get_contents($value);
            return $this->cleanString($data === false ? null : $data);
        }
        if (is_string($value)) {
            return $this->cleanString($value);
        }
        if (is_float($value) && !is_finite($value)) {
            return null;
        }
        return $value;
    }

    private function cleanString($value)
    {
        if ($value === null) {
            return null;
        }
        $value = (string) $value;
        if ($value === '' || mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }
        // Best effort: Japanese legacy encodings first (VMI/EMS DBs), then
        // western single-byte, then strip anything still invalid.
        $converted = @mb_convert_encoding(
            $value,
            'UTF-8',
            'CP932, SJIS, EUC-JP, ISO-8859-1, Windows-1252'
        );
        if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
            return $converted;
        }
        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }

    /**
     * Turns a driver error into an actionable RuntimeException: which
     * connection/host/database failed, the driver message, and — for
     * connectivity failures (SQLSTATE 08001) — a hint to check the DB_* env
     * and network/VPN. Never includes credentials.
     */
    private function sqlExecutionError(string $conn, \Throwable $e): \RuntimeException
    {
        $cfg = config("database.connections.{$conn}", []);
        $host = $cfg['host'] ?? '?';
        $port = $cfg['port'] ?? '?';
        $db = $cfg['database'] ?? '?';
        $msg = trim($e->getMessage());

        \Log::warning("Dataset SQL error on connection '{$conn}' ({$host}/{$db}): {$msg}");

        $hint = '';
        $connectivity = str_contains($msg, '08001')
            || stripos($msg, 'unreachable host') !== false
            || stripos($msg, 'network-related') !== false
            || stripos($msg, 'could not open a connection') !== false
            || stripos($msg, 'login timeout') !== false
            || stripos($msg, 'server was not found') !== false;

        if ($connectivity) {
            $hint = " The SQL Server host ({$host}:{$port}) could not be reached from the API server. "
                . "Check the DB_* env for this connection and network/VPN access.";
        }

        return new \RuntimeException(
            "Query failed on connection '{$conn}' ({$host}/{$db}). {$msg}{$hint}"
        );
    }

    private function runApiDataset($type, $endpoint, $method, $payloadTpl, $headers, array $schema, array $params, int $limit): array
    {
        // api_ext hits an absolute external URL; api hits this same API.
        $external = $type === 'api_ext';
        $endpoint = trim((string) $endpoint);

        if (!$external) {
            $endpoint = ltrim($endpoint, '/');
            if (str_starts_with($endpoint, 'api/')) {
                $endpoint = substr($endpoint, 4);
            }
        }

        // Fill declared params first so missing ones fall back to defaults.
        $resolved = [];
        foreach ($schema as $def) {
            if (empty($def['name'])) {
                continue;
            }
            $resolved[$def['name']] = $params[$def['name']] ?? ($def['default'] ?? '');
        }
        foreach ($resolved as $key => $val) {
            $endpoint = str_replace('{{' . $key . '}}', rawurlencode((string) $val), $endpoint);
        }

        $payload = [];
        if (!empty($payloadTpl)) {
            $tpl = is_string($payloadTpl) ? $payloadTpl : json_encode($payloadTpl);
            foreach ($resolved as $key => $val) {
                $tpl = str_replace('{{' . $key . '}}', (string) $val, $tpl);
            }
            $decoded = json_decode($tpl, true);
            $payload = is_array($decoded) ? $decoded : [];
        }

        $url = $external
            ? $endpoint
            : rtrim(config('app.url'), '/') . '/api/' . $endpoint;

        // Internal calls carry the portal identity so the target endpoint can
        // apply its own role checks. External calls only get configured headers
        // (internal identity is never leaked to third parties).
        $requestHeaders = [];
        if (!$external) {
            $requestHeaders['username'] = (string) request()->header('username', '');
            $requestHeaders['roleid'] = (string) request()->header('roleid', '');
        }
        $requestHeaders = array_merge($requestHeaders, is_array($headers) ? $headers : []);

        $http = Http::timeout(30)->acceptJson()->withHeaders($requestHeaders);

        $resp = strtolower($method ?? 'get') === 'post'
            ? $http->post($url, $payload)
            : $http->get($url, $payload);

        if (!$resp->ok()) {
            throw new \RuntimeException('API request failed (HTTP ' . $resp->status() . ').');
        }

        $json = $resp->json();
        $rows = is_array($json) && array_key_exists('data', $json) ? $json['data'] : $json;
        if (!is_array($rows)) {
            return [];
        }

        $rows = array_values(array_filter(array_map(function ($r) {
            if (is_object($r)) {
                return $this->cleanRow((array) $r);
            }
            return is_array($r) ? $this->cleanRow($r) : null;
        }, $rows)));

        return array_slice($rows, 0, $limit);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Accepts either an object map { "Header": "value" } or a list of
     * { key, value } rows (what the editor UI sends). Skips empty names and
     * rejects names that could break the HTTP client.
     */
    private function normalizeHeaders($headers): array
    {
        if (!is_array($headers)) {
            return [];
        }

        $out = [];
        foreach ($headers as $key => $value) {
            $name = is_array($value) ? (string) ($value['key'] ?? '') : (string) $key;
            $val = is_array($value) ? ($value['value'] ?? '') : $value;
            $name = trim($name);

            if ($name === '') {
                continue;
            }
            if (!preg_match('/^[A-Za-z0-9\-_]+$/', $name)) {
                throw new \RuntimeException("Invalid header name: {$name}");
            }
            if (in_array(strtolower($name), ['host', 'content-length', 'connection', 'transfer-encoding'], true)) {
                throw new \RuntimeException("Header not allowed: {$name}");
            }
            if (!is_scalar($val)) {
                continue;
            }
            $out[$name] = (string) $val;
        }

        return $out;
    }

    private function normalizeParamsSchema($schema): array
    {
        if (!is_array($schema)) {
            return [];
        }

        $out = [];
        foreach ($schema as $def) {
            $name = trim((string) ($def['name'] ?? ''));
            if ($name === '' || !preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $name)) {
                continue;
            }
            $out[] = [
                'name' => $name,
                'label' => (string) ($def['label'] ?? $name),
                'type' => in_array($def['type'] ?? 'string', ['string', 'number', 'date'], true) ? $def['type'] : 'string',
                'default' => $def['default'] ?? null,
            ];
        }
        return $out;
    }

    /**
     * Only declared params may reach the query, and only as scalars.
     */
    private function sanitizeParams(array $params, array $schema): array
    {
        $allowed = [];
        foreach ($schema as $def) {
            if (empty($def['name']) || !array_key_exists($def['name'], $params)) {
                continue;
            }
            $val = $params[$def['name']];
            if (!is_scalar($val)) {
                continue;
            }
            $allowed[$def['name']] = ($def['type'] ?? 'string') === 'number'
                ? $val + 0
                : (string) $val;
        }
        return $allowed;
    }

    private function normalizeRoles($roles): array
    {
        if (!is_array($roles)) {
            return [];
        }
        return array_values(array_filter(array_map('strval', $roles), fn ($r) => $r !== ''));
    }

    /**
     * Returns a 403 response when the dataset is role-restricted and the
     * caller's role is not on the allowlist. Empty allowlist = everyone.
     */
    private function rolesDenied($rolesJson, string $roleId)
    {
        $roles = $this->decodeJson($rolesJson) ?: [];
        if (count($roles) === 0) {
            return null;
        }
        if ($roleId !== '' && in_array($roleId, $roles, true)) {
            return null;
        }
        return response([
            'status' => false,
            'message' => 'You do not have access to this dataset.',
        ], 403);
    }

    private function decodeJson($value)
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    private function extractColumns(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }
        return array_keys($rows[0]);
    }

    /**
     * Never leak raw DB/HTTP error internals (they can contain credentials
     * or schema details) — only our own RuntimeException messages are shown.
     */
    private function safeErrorMessage(\Throwable $e): string
    {
        if ($e instanceof \RuntimeException) {
            return $e->getMessage();
        }
        \Log::warning('Dataset execution failed: ' . $e->getMessage());
        return 'Dataset execution failed. Check the query/endpoint definition.';
    }
}
