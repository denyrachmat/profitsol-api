@extends('install.layout')

@section('title', 'Installation complete')

@section('content')
<div class="text-center py-3">
    <div class="display-4">🎉</div>
    <h2 class="h4 mt-2">Installation complete</h2>
    <p class="text-muted">Your application is ready. The installer is now locked.</p>
</div>

@if(!empty($log))
    <h3 class="h6">Install log</h3>
    <pre class="bg-dark text-light p-3 rounded small">{{ implode("\n", (array) $log) }}</pre>
@endif

<div class="alert alert-info">
    <strong>Next steps</strong>
    <ul class="mb-0">
        <li>Verify <code>{{ $appUrl }}</code> and your API endpoints respond.</li>
        <li>Configure queue / scheduler / Horizon if needed.</li>
        <li>Optional extra DBs (EMS, MEGA, PSI, WIS…) can be added manually to <code>.env</code>.</li>
        <li>Delete or restrict <code>/install</code> is not needed — it auto-blocks while <code>storage/installed</code> exists.</li>
    </ul>
</div>

<div class="d-flex justify-content-center gap-2">
    <a href="/" class="btn btn-primary">Go to application</a>
</div>
@endsection
