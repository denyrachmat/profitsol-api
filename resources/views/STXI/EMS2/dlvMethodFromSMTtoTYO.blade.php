<div>
    <h1 style="text-align: left;">Hello {{$user}},</h1>
    <p>Here is our Plan Delivery for date : <b>{{$date}}</b></p>

    <table style="border-collapse:collapse;border-color:#aaa;border-spacing:0" class="tg">
        <thead>
            <tr>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th colspan="3" style="background-color:#f38630;border-color:inherit;border-style:solid;border-width:1px;color:#fff;font-family:Arial, sans-serif;font-size:14px;font-weight:normal;overflow:hidden;padding:10px 5px;text-align:center;vertical-align:top;word-break:normal">SGL Direct Pickup</th>
            </tr>
            <tr>
                <th style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#000;font-family:Arial, sans-serif;font-size:14px;font-weight:normal;overflow:hidden;padding:10px 5px;text-align:center;vertical-align:top;word-break:normal"><b>No</b></th>
                <th style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#000;font-family:Arial, sans-serif;font-size:14px;font-weight:normal;overflow:hidden;padding:10px 5px;text-align:center;vertical-align:top;word-break:normal"><b>Model</b></th>
                <th style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#000;font-family:Arial, sans-serif;font-size:14px;font-weight:normal;overflow:hidden;padding:10px 5px;text-align:center;vertical-align:top;word-break:normal"><b>Model Desc</b></th>
                <th style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#000;font-family:Arial, sans-serif;font-size:14px;font-weight:normal;overflow:hidden;padding:10px 5px;text-align:center;vertical-align:top;word-break:normal"><b>Delivery</b></th>
                <th style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#000;font-family:Arial, sans-serif;font-size:14px;font-weight:normal;overflow:hidden;padding:10px 5px;text-align:center;vertical-align:top;word-break:normal"><b>With Barcode</b></th>
                <th style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#000;font-family:Arial, sans-serif;font-size:14px;font-weight:normal;overflow:hidden;padding:10px 5px;text-align:center;vertical-align:top;word-break:normal"><b>STXI SPQ <br>/ Box Data</b></th>
                <th style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#000;font-family:Arial, sans-serif;font-size:14px;font-weight:normal;overflow:hidden;padding:10px 5px;text-align:center;vertical-align:top;word-break:normal"><b>Without Barcode</b></th>
                <th style="background-color:yellow;border-color:inherit;border-style:solid;border-width:1px;color:#000;font-family:Arial, sans-serif;font-size:14px;font-weight:normal;overflow:hidden;padding:10px 5px;text-align:center;vertical-align:top;word-break:normal"><b>SMT Delivery</b></th>
                <th style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#000;font-family:Arial, sans-serif;font-size:14px;font-weight:normal;overflow:hidden;padding:10px 5px;text-align:center;vertical-align:top;word-break:normal">Rank</th>
                <th style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#000;font-family:Arial, sans-serif;font-size:14px;font-weight:normal;overflow:hidden;padding:10px 5px;text-align:center;vertical-align:top;word-break:normal">IPP</th>
                <th style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#000;font-family:Arial, sans-serif;font-size:14px;font-weight:normal;overflow:hidden;padding:10px 5px;text-align:center;vertical-align:top;word-break:normal">Job No</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $key => $value)
                <tr>
                    <td style="background-color:{{$value['DRST_SPLITDOC'] == 1 ? '#e9f507': '#fff'}};border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">{{$key + 1}}</td>
                    <td style="background-color:{{$value['DRST_SPLITDOC'] == 1 ? '#e9f507': '#fff'}};border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">{{$value['MITM_MODELCD']}}</td>
                    <td style="background-color:{{$value['DRST_SPLITDOC'] == 1 ? '#e9f507': '#fff'}};border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">{{$value['MITM_ITMD1']}}</td>
                    <td style="background-color:{{$value['DRST_SPLITDOC'] == 1 ? '#e9f507': '#fff'}};border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:right;vertical-align:top;word-break:normal">{{$value['TOT_INC_DLV']}}</td>
                    <td style="background-color:{{$value['DRST_SPLITDOC'] == 1 ? '#e9f507': '#fff'}};border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:right;vertical-align:top;word-break:normal">{{$value['TOT_OUT_BC_DLV']}}</td>
                    <td style="background-color:{{$value['DRST_SPLITDOC'] == 1 ? '#e9f507': '#fff'}};border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">
                        @if($value['SPQ'] !== '0')
                        @foreach($value['SPQ'] as $keySPQ => $valSPQ)
                            {{$valSPQ}} <br>
                        @endforeach
                        @else
                            {{$value['SPQ']}}
                        @endif
                    </td>
                    <td style="background-color:{{$value['DRST_SPLITDOC'] == 1 ? '#e9f507': '#fff'}};border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:right;vertical-align:top;word-break:normal">{{$value['TOT_OUT_WOBC_DLV']}}</td>
                    <td style="background-color:{{$value['DRST_SPLITDOC'] == 1 ? '#e9f507': '#fff'}};border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:right;vertical-align:top;word-break:normal">{{$value['TOT_SMT_DLV']}}</td>
                    <td style="background-color:{{$value['DRST_SPLITDOC'] == 1 ? '#e9f507': '#fff'}};border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">{{$value['RANK_REMARK']}}</td>
                    <td style="background-color:{{$value['DRST_SPLITDOC'] == 1 ? '#e9f507': '#fff'}};border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">{{$value['IPP_REMARK']}}</td>
                    <td style="background-color:{{$value['DRST_SPLITDOC'] == 1 ? '#e9f507': '#fff'}};border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">{{$value['DRST_JOBNO']}}</td>
                    @if($value['DRST_SPLITDOC'] == 1)
                        <td style="background-color:{{$value['DRST_SPLITDOC'] == 1 ? '#e9f507': '#fff'}};border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:left;vertical-align:top;word-break:normal">{{$value['DRST_SPLITDOCRMK']}}</td>
                    @endif
                </tr>
            @endforeach
            <tr>
                <td></td>
                <td style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:center;vertical-align:top;word-break:normal" colspan="2"><b>Total</b></td>
                <td style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:right;vertical-align:top;word-break:normal"><b>{{number_format($totalDelivery)}}</b></td>
                <td style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:right;vertical-align:top;word-break:normal"><b>{{number_format($totalWBarcode)}}</b></td>
                <td></td>
                <td style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:right;vertical-align:top;word-break:normal"><b>{{number_format($totalWOBarcode)}}</b></td>
                <td style="background-color:#fff;border-color:inherit;border-style:solid;border-width:1px;color:#333;font-family:Arial, sans-serif;font-size:14px;overflow:hidden;padding:10px 5px;text-align:right;vertical-align:top;word-break:normal"><b>{{number_format($totalSMTDlv)}}</b></td>
            </tr>
        </tbody>
    </table>
    
    <br>
    <p>Best Regards,</p>
    <p><b>PT Sumitronics Indonesia</b></p>
</div>