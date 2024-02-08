<div>
    <h2>CIRCULAR {{ $ten }}</h2>
</div>

<div style="padding-top: 2em">
    <h3>Date : {{date('d M Y', strtotime($mail_date))}}</h3>
</div>
<div>
    <h3>Subject: {{$subject}}</h3>
</div>

<div style="padding-top: 3em">
    <b>1). Model :</b>
    <br>
    {{implode(', ', $model)}}
</div>

<div style="padding-top: 2em">
    <b>2). Content :</b>
    <br>
    <table border="1 solid" style="
    ">
        {!! $content !!}
    </table>
</div>

<div style="padding-top: 2em">
    <b>3). Issued Document :</b>
    <br>
    @foreach($list_files as $file)
        <a target="_blank" href="{{asset('storage/circular_ten/'.$ten.'/'.basename($file))}}">{{basename($file)}}</a><br>
    @endforeach
</div>

<div style="padding-top: 2em">
    <b>Execution Schedule:</b> {{$exec_sch}}
    <br>
    <b>Reason:</b> {{$reason}}
</div>