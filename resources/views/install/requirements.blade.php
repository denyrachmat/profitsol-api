@extends('install.layout')

@section('title', 'Requirements')

@section('content')
<h2 class="h5 mb-3">Step 1 — Server requirements</h2>

@if($alreadyHasEnv)
    <div class="alert alert-warning">
        An <code>.env</code> file already exists. Continuing will back it up
        (<code>.env.backup-*</code>) and overwrite it.
    </div>
@endif

<table class="table table-bordered align-middle">
    <thead class="table-light">
        <tr><th>Check</th><th>Value</th><th>Status</th></tr>
    </thead>
    <tbody>
    @foreach($checks as $check)
        <tr>
            <td>{{ $check['label'] }}</td>
            <td class="small text-muted text-break">{{ $check['value'] }}</td>
            <td>
                @if($check['passed'])
                    <span class="badge bg-success">OK</span>
                @elseif($check['critical'])
                    <span class="badge bg-danger">Failed</span>
                    <div class="small text-muted">{{ $check['hint'] }}</div>
                @else
                    <span class="badge bg-warning text-dark">Missing (optional)</span>
                    <div class="small text-muted">{{ $check['hint'] }}</div>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

@if(!$canContinue)
    <div class="alert alert-danger">Fix the failed checks above, then refresh this page.</div>
@endif

<div class="d-flex justify-content-end">
    <a href="{{ route('install.database') }}"
       class="btn btn-primary {{ $canContinue ? '' : 'disabled' }}">Continue to database setup →</a>
</div>
@endsection
