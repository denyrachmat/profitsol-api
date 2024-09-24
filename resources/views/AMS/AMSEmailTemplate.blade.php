@component('mail::message')
  # {{$data->subject}}
  {!!$data->content!!}
  <!-- @component('mail::button', ['url' => $approve])
  Approve
  @endcomponent

  @component('mail::button', ['url' => $reject])
  Reject
  @endcomponent -->
@endcomponent
