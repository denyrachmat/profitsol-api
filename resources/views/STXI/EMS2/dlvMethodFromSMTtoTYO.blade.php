<div>
    <h1 style="text-align: left;">Hello {{$user}},</h1>
    <p>Here is our Plan Delivery for date : <b>{{$date}}</b></p>

    <table style="border-collapse:collapse;border-color:#aaa;border-spacing:0" class="tg">
        <thead>
            <tr>
                <th style="background-color:#f38630;border-color:inherit;border-style:solid;border-width:1px;color:#fff;font-family:Arial, sans-serif;font-size:14px;font-weight:normal;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">No</th>
                <th style="background-color:#f38630;border-color:inherit;border-style:solid;border-width:1px;color:#fff;font-family:Arial, sans-serif;font-size:14px;font-weight:normal;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">Model</th>
                <th style="background-color:#f38630;border-color:inherit;border-style:solid;border-width:1px;color:#fff;font-family:Arial, sans-serif;font-size:14px;font-weight:normal;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">Model Desc</th>
                <th style="background-color:#f38630;border-color:inherit;border-style:solid;border-width:1px;color:#fff;font-family:Arial, sans-serif;font-size:14px;font-weight:normal;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">Delivery</th>
                <th style="background-color:#f38630;border-color:inherit;border-style:solid;border-width:1px;color:#fff;font-family:Arial, sans-serif;font-size:14px;font-weight:normal;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">With Barcode</th>
                <th style="background-color:#f38630;border-color:inherit;border-style:solid;border-width:1px;color:#fff;font-family:Arial, sans-serif;font-size:14px;font-weight:normal;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">STXI SPQ / Box Data</th>
                <th style="background-color:#f38630;border-color:inherit;border-style:solid;border-width:1px;color:#fff;font-family:Arial, sans-serif;font-size:14px;font-weight:normal;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">Without Barcode</th>
                <th style="background-color:#f38630;border-color:inherit;border-style:solid;border-width:1px;color:#fff;font-family:Arial, sans-serif;font-size:14px;font-weight:normal;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">SMT Delivery</th>
                <th style="background-color:#f38630;border-color:inherit;border-style:solid;border-width:1px;color:#fff;font-family:Arial, sans-serif;font-size:14px;font-weight:normal;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal"></th>
                <th style="background-color:#f38630;border-color:inherit;border-style:solid;border-width:1px;color:#fff;font-family:Arial, sans-serif;font-size:14px;font-weight:normal;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $key => $value)
                <tr>
                    <td style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">{{$key + 1}}</td>
                    <td style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">{{$value['MITM_MODELCD']}}</td>
                    <td style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">{{$value['MITM_ITMD1']}}</td>
                    <td style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">{{$value['TOT_INC_DLV']}}</td>
                    <td style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">{{$value['TOT_OUT_BC_DLV']}}</td>
                    <td style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">{{$value['SPQ']}}</td>
                    <td style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">{{$value['TOT_OUT_WOBC_DLV']}}</td>
                    <td style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">{{$value['TOT_SMT_DLV']}}</td>
                    <td style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">{{$value['IPP_REMARK']}}</td>
                    <td style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">{{$value['RANK_REMARK']}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <br>
    <p>Best Regards,</p>
    <p><b>PT Sumitronics Indonesia</b></p>
</div>