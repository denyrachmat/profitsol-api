@component('mail::message')
  # Introduction
  {!!$data->content!!}
  @component('mail::button', ['url' => $approve])
  Approve
  @endcomponent

  @component('mail::button', ['url' => $reject])
  Reject
  @endcomponent

  or using this URL to view detail : {{env('FE_URL')}}/approvalAction/{{$data->token}}

  Thanks,<br>
  {{ config('app.name') }}
@endcomponent
