@extends('install.layout')

@section('title', 'Database & Environment')

@section('content')
<h2 class="h5 mb-3">Step 2 — Application &amp; core databases</h2>
<p class="text-muted">All 7 core databases below are <strong>required</strong>. You can change driver, host, port, name and credentials per database. Use <em>Test</em> to verify before saving.</p>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('install.store') }}">
    @csrf

    <div class="db-card">
        <h3 class="h6">Application</h3>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">App name</label>
                <input type="text" name="app_name" class="form-control" required
                       value="{{ old('app_name', $env['APP_NAME'] ?? 'ProfitSol') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">App URL</label>
                <input type="url" name="app_url" class="form-control" required
                       value="{{ old('app_url', $env['APP_URL'] ?? '') }}"
                       placeholder="https://api.example.com">
            </div>
            <div class="col-md-4">
                <label class="form-label">Environment</label>
                <select name="app_env" class="form-select">
                    <option value="production" {{ old('app_env', $env['APP_ENV'] ?? 'production') === 'production' ? 'selected' : '' }}>production</option>
                    <option value="local" {{ old('app_env', $env['APP_ENV'] ?? '') === 'local' ? 'selected' : '' }}>local</option>
                </select>
            </div>
        </div>
    </div>

    @foreach($groups as $key => $group)
        @php($p = $group['prefix'])
        <div class="db-card" data-db-group="{{ $key }}">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h6 mb-0">{{ $group['label'] }} <span class="badge bg-secondary">required</span></h3>
                <button type="button" class="btn btn-sm btn-outline-primary test-btn" data-group="{{ $key }}">Test</button>
            </div>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Driver</label>
                    <select name="db[{{ $key }}][driver]" class="form-select driver-select">
                        @foreach($drivers as $driver)
                            <option value="{{ $driver }}"
                                {{ old("db.$key.driver", $env[$p.'DRIVER'] ?? '') === $driver ? 'selected' : '' }}>
                                {{ $driver }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">
                        driver &rarr; <code class="env-key">{{ $p }}DRIVER</code><br>
                        name &rarr; <code class="env-key">{{ $p }}CONNECTION={{ $group['connection'] }}</code>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Host</label>
                    <input type="text" name="db[{{ $key }}][host]" class="form-control host-input"
                           value="{{ old("db.$key.host", $env[$p.'HOST'] ?? '127.0.0.1') }}">
                    <div class="form-text"><code class="env-key">{{ $p }}HOST</code></div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Port</label>
                    <input type="text" name="db[{{ $key }}][port]" class="form-control port-input"
                           value="{{ old("db.$key.port", $env[$p.'PORT'] ?? '1433') }}">
                    <div class="form-text"><code class="env-key">{{ $p }}PORT</code></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Database</label>
                    <input type="text" name="db[{{ $key }}][database]" class="form-control db-input" required
                           value="{{ old("db.$key.database", $env[$p.'DATABASE'] ?? '') }}">
                    <div class="form-text"><code class="env-key">{{ $p }}DATABASE</code></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="db[{{ $key }}][username]" class="form-control user-input"
                           value="{{ old("db.$key.username", $env[$p.'USERNAME'] ?? '') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="db[{{ $key }}][password]" class="form-control pass-input" autocomplete="new-password"
                           value="{{ old("db.$key.password", $env[$p.'PASSWORD'] ?? '') }}">
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="test-result text-muted w-100" id="test-result-{{ $key }}"></div>
                </div>
            </div>
        </div>
    @endforeach

    <div class="d-flex justify-content-between mt-3">
        <a href="{{ route('install.index') }}" class="btn btn-outline-secondary">← Back</a>
        <button type="submit" class="btn btn-primary">Save .env &amp; continue →</button>
    </div>
</form>
@endsection

@section('scripts')
<script>
document.querySelectorAll('.test-btn').forEach(function (btn) {
    btn.addEventListener('click', async function () {
        const key = btn.dataset.group;
        const card = document.querySelector('[data-db-group="' + key + '"]');
        const result = document.getElementById('test-result-' + key);
        const val = (sel) => card.querySelector(sel).value;
        result.textContent = 'Testing…';
        result.className = 'test-result text-muted w-100';
        btn.disabled = true;
        try {
            const res = await fetch('{{ route('install.test') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    driver: val('.driver-select'),
                    host: val('.host-input'),
                    port: val('.port-input'),
                    database: val('.db-input'),
                    username: val('.user-input'),
                    password: val('.pass-input')
                })
            });
            const data = await res.json();
            result.textContent = data.message || (res.ok ? 'OK' : 'Failed');
            result.className = 'test-result w-100 ' + (res.ok ? 'text-success' : 'text-danger');
        } catch (e) {
            result.textContent = 'Request failed: ' + e.message;
            result.className = 'test-result text-danger w-100';
        } finally {
            btn.disabled = false;
        }
    });
});
</script>
@endsection
