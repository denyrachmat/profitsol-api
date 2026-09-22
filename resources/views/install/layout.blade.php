<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Installation — @yield('title', 'Setup Wizard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f1f5f9; }
        .installer-card { max-width: 960px; margin: 3rem auto; }
        .steps { display: flex; gap: .5rem; margin-bottom: 1.5rem; }
        .step { flex: 1; text-align: center; font-size: .85rem; padding: .6rem .25rem; border-radius: .5rem; background: #e2e8f0; color: #475569; }
        .step.active { background: #0d6efd; color: #fff; font-weight: 600; }
        .step.done { background: #bbf7d0; color: #166534; }
        .db-card { border: 1px solid #e2e8f0; border-radius: .75rem; padding: 1.25rem; margin-bottom: 1rem; background: #fff; }
        .test-result { font-size: .85rem; min-height: 1.4em; }
        code.env-key { font-size: .78rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="card shadow-sm installer-card">
        <div class="card-body p-4 p-md-5">
            <h1 class="h3 mb-1">ProfitSol API — Installation</h1>
            <p class="text-muted mb-4">WordPress-style setup. This wizard creates your <code>.env</code> file.</p>

            @php($currentStep = $step ?? 1)
            <div class="steps">
                <div class="step {{ $currentStep == 1 ? 'active' : ($currentStep > 1 ? 'done' : '') }}">1. Requirements</div>
                <div class="step {{ $currentStep == 2 ? 'active' : ($currentStep > 2 ? 'done' : '') }}">2. Database &amp; Env</div>
                <div class="step {{ $currentStep == 3 ? 'active' : ($currentStep > 3 ? 'done' : '') }}">3. Install</div>
                <div class="step {{ $currentStep == 4 ? 'active' : '' }}">4. Complete</div>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @yield('content')

            <hr class="my-4">
            <p class="text-muted small mb-0">
                Re-running the installer is blocked once <code>storage/installed</code> exists.
                To reinstall, restore from backup or delete <code>.env</code> and <code>storage/installed</code>.
            </p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@yield('scripts')
</body>
</html>
