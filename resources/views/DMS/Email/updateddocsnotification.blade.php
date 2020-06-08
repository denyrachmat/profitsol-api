<div>
    <h2>Hello {{$user}},</h2>
    <p>We have to inform you about your rejected document has been updated, please login and check your notification at below links.</p>
    <p><a href="http://192.168.100.32:8081/dms">STX DMS</a></p>
    <hr>
    <table class="paleBlueRows">
        <thead>
            <tr>
                <td>No</td>
                <td>Document Name</td>
                <td>Uploaded Time</td>
                <td>Version Code</td>
                <td>Latest Approver</td>
                <td>Latest Comment</td>
            </tr>
        </thead>
        <tbody>
            @foreach($data_doc as $key => $file)
            @if($key == 0)
            <tr>
                <td style="text-align: center;" colspan="6"><b>Current Document</b></td>
            </tr>
            <tr>
                <td>{{$key+1}}</td>
                <td>{{$file->doc->doc_real_name}}</td>
                <td>{{$file->doc->created_at}}</td>
                <td>{{$file->ver_code}}</td>
                <td>{{$file->doc->apprvhist !== null ? $file->doc->apprvhist->apprv_hist_user : null}}</td>
                <td>{{$file->doc->apprvhist !== null ? $file->doc->apprvhist->apprv_hist_comment : null}}</td>
            </tr>
            <tr>
                <td style="text-align: center;" colspan="6"><b>Previous Document</b></td>
            </tr>
            @else
            <tr>
                <td>{{$key+1}}</td>
                <td>{{$file->doc->doc_real_name}}</td>
                <td>{{$file->doc->created_at}}</td>
                <td>{{$file->ver_code}}</td>
                <td>{{$file->doc->apprvhist !== null ? $file->doc->apprvhist->apprv_hist_user : null}}</td>
                <td>{{$file->doc->apprvhist !== null ? $file->doc->apprvhist->apprv_hist_comment : null}}</td>
            </tr>
            @endif
            @endforeach
        </tbody>
    </table>

    <style>
        table.paleBlueRows {
            font-family: "Times New Roman", Times, serif;
            border: 1px solid #FFFFFF;
            width: 100%;
            height: 200px;
            text-align: center;
            border-collapse: collapse;
        }

        table.paleBlueRows td,
        table.paleBlueRows th {
            border: 1px solid #FFFFFF;
            padding: 3px 2px;
        }

        table.paleBlueRows tbody td {
            font-size: 13px;
        }

        table.paleBlueRows tr:nth-child(even) {
            background: #D0E4F5;
        }

        table.paleBlueRows thead {
            background: #0B6FA4;
            border-bottom: 5px solid #FFFFFF;
        }

        table.paleBlueRows thead th {
            font-size: 17px;
            font-weight: bold;
            color: #FFFFFF;
            text-align: center;
            border-left: 2px solid #FFFFFF;
        }

        table.paleBlueRows thead th:first-child {
            border-left: none;
        }

        table.paleBlueRows tfoot td {
            font-size: 14px;
        }
    </style>
</div>