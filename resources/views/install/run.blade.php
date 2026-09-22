@extends('install.layout')

@section('title', 'Run installation')

@section('content')
<h2 class="h5 mb-3">Step 3 — Run installation</h2>
<p class="text-muted">
    Your <code>.env</code> is written and <code>APP_KEY</code> generated.
    This step runs <code>php artisan migrate --force</code> and <code>php artisan storage:link</code>.
</p>

@if(session('install_log'))
    <pre class="bg-dark text-light p-3 rounded small">{{ implode("\n", (array) session('install_log')) }}</pre>
@endif

<form method="POST" action="{{ route('install.execute') }}">
    @csrf
    <div class="d-flex justify-content-between">
        <a href="{{ route('install.database') }}" class="btn btn-outline-secondary">← Back</a>
        <button type="submit" class="btn btn-success">Run migrations now</button>
    </div>
</form>
@endsection
