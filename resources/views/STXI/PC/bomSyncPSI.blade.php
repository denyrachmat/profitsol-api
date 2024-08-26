<style type="text/css">
    .tg {
        border-collapse: collapse;
        border-color: #ccc;
        border-spacing: 0;
        width: 100%;
    }

    .tg td {
        background-color: #fff;
        border-bottom-width: 1px;
        border-color: #ccc;
        border-style: solid;
        border-top-width: 1px;
        border-width: 0px;
        color: #333;
        font-family: Arial, sans-serif;
        font-size: 14px;
        overflow: hidden;
        padding: 10px 5px;
        word-break: normal;
    }

    .tg th {
        background-color: #f0f0f0;
        border-bottom-width: 1px;
        border-color: #ccc;
        border-style: solid;
        border-top-width: 1px;
        border-width: 0px;
        color: #333;
        font-family: Arial, sans-serif;
        font-size: 14px;
        font-weight: normal;
        overflow: hidden;
        padding: 10px 5px;
        word-break: normal;
    }

    .tg .tg-0pky {
        border-color: inherit;
        text-align: left;
        vertical-align: top
    }

    .tg .tg-btxf {
        background-color: #f9f9f9;
        border-color: inherit;
        text-align: left;
        vertical-align: top
    }
</style>
<div>
    <h3 style="text-align: left;">Dear All,</h3>
    <p>Please kindly proceed input PO & Sales Invoice for DO SME as below</p>

    <table class="tg">
        <thead>
            <tr>
                <th class="tg-0pky">No</th>
                <th class="tg-0pky">Model Code</th>
                <th class="tg-0pky">Model Desc</th>
                <th class="tg-0pky">Revision</th>
                <th class="tg-0pky">IEI Ten No</th>
                <th class="tg-0pky">Change Overview</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $key => $value)
                <tr>
                    <td class="tg-btxf">{{$key + 1}}</td>
                    <td class="tg-btxf">{{$value['MODEL_CODE']}}</td>
                    <td class="tg-btxf">{{$value['MODEL_DESC']}}</td>
                    <td class="tg-btxf">{{$value['REVISION']}}</td>
                    <td class="tg-btxf">{{$value['IEI_TEN_NO']}}</td>
                    <td class="tg-btxf">{{$value['CHANGE_OVERVIEW']}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <br>
    <div style="top-padding:1em">
        <p>Best Regards,</p>
        <p><b>PT Sumitronics Indonesia</b></p>
    </div>
</div>
