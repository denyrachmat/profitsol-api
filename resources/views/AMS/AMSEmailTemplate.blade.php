@component('mail::message')
  # Introduction

  @component('mail::button', ['url' => $approve])
  Approve
  @endcomponent

  @component('mail::button', ['url' => $reject])
  Reject
  @endcomponent

  Thanks,<br>
  {{ config('app.name') }}
@endcomponent
