<?php 
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>
<h1 style="text-align: left;">Hello {{$user->first_name}},</h1>
<p>We have to inform you about a pending approval which is you need to approve, because the author waiting the document to fully approved.</p>
<p>Click link below to login DMS.</p>
<p><a href="http://192.168.100.32:8081/dms">Login to DMS</a></p>
<table class="tg" style="border-collapse: collapse;border-color: #C44D58;border-spacing: 0;width:100%">
<thead>
  <tr>
    <th class="tg-0pky" style="background-color: #FE4365;border-color: inherit;border-style: solid;border-width: 0px;color: #fdf6e3;font-family: Arial, sans-serif;font-size: 14px;font-weight: normal;overflow: hidden;padding: 10px 5px;word-break: normal;text-align: left;vertical-align: top;">No</th>
    <th class="tg-0lax" style="background-color: #FE4365;border-color: #C44D58;border-style: solid;border-width: 0px;color: #fdf6e3;font-family: Arial, sans-serif;font-size: 14px;font-weight: normal;overflow: hidden;padding: 10px 5px;word-break: normal;text-align: left;vertical-align: top;">Document Name</th>
    <th class="tg-0lax" style="background-color: #FE4365;border-color: #C44D58;border-style: solid;border-width: 0px;color: #fdf6e3;font-family: Arial, sans-serif;font-size: 14px;font-weight: normal;overflow: hidden;padding: 10px 5px;word-break: normal;text-align: left;vertical-align: top;">Receive Document Time</th>
    <th class="tg-0lax" style="background-color: #FE4365;border-color: #C44D58;border-style: solid;border-width: 0px;color: #fdf6e3;font-family: Arial, sans-serif;font-size: 14px;font-weight: normal;overflow: hidden;padding: 10px 5px;word-break: normal;text-align: left;vertical-align: top;">Last Approved User</th>
    <th class="tg-0lax" style="background-color: #FE4365;border-color: #C44D58;border-style: solid;border-width: 0px;color: #fdf6e3;font-family: Arial, sans-serif;font-size: 14px;font-weight: normal;overflow: hidden;padding: 10px 5px;word-break: normal;text-align: left;vertical-align: top;">Last User Comment</th>
  </tr>
</thead>
<tbody>
@foreach($data as $key => $val)
  <tr>
    <td class="tg-0lax" style="background-color: #F9CDAD;border-color: #C44D58;border-style: solid;border-width: 0px;color: #002b36;font-family: Arial, sans-serif;font-size: 14px;overflow: hidden;padding: 10px 5px;word-break: normal;text-align: left;vertical-align: top;">{{$key+1}}</td>
    <td class="tg-0lax" style="background-color: #F9CDAD;border-color: #C44D58;border-style: solid;border-width: 0px;color: #002b36;font-family: Arial, sans-serif;font-size: 14px;overflow: hidden;padding: 10px 5px;word-break: normal;text-align: left;vertical-align: top;">{{$val->doc->doc_real_name}}</td>
    <td class="tg-0lax" style="background-color: #F9CDAD;border-color: #C44D58;border-style: solid;border-width: 0px;color: #002b36;font-family: Arial, sans-serif;font-size: 14px;overflow: hidden;padding: 10px 5px;word-break: normal;text-align: left;vertical-align: top;">{{$val->created_at}} ( {{time_elapsed_string($val->created_at)}} )</td>
    <td class="tg-0lax" style="background-color: #F9CDAD;border-color: #C44D58;border-style: solid;border-width: 0px;color: #002b36;font-family: Arial, sans-serif;font-size: 14px;overflow: hidden;padding: 10px 5px;word-break: normal;text-align: left;vertical-align: top;">{{$val->users->first_name}}</td>
    <td class="tg-0lax" style="background-color: #F9CDAD;border-color: #C44D58;border-style: solid;border-width: 0px;color: #002b36;font-family: Arial, sans-serif;font-size: 14px;overflow: hidden;padding: 10px 5px;word-break: normal;text-align: left;vertical-align: top;">{{$val->apprv_hist_comment}}</td>
  </tr>
@endforeach
</tbody>
</table>