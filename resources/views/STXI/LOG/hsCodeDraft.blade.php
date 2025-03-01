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
        vertical-align: top;
        max-width: 50px;
        word-wrap: break-word;
    }
</style>
<div>
    <table class="tg">
        <thead>
            <tr>
                <th class="tg-0pky">HS Code Doc No.</th>
                <th class="tg-0pky">Item Code</th>
                <th class="tg-0pky">Part Name</th>
                <th class="tg-0pky">Item Desc 1</th>
                <th class="tg-0pky">Item Desc 2</th>
                <th class="tg-0pky">Maker HS Code</th>
                <th class="tg-0pky">STX-I HS Code</th>
                <th class="tg-0pky">Tataniaga Border</th>
                <th class="tg-0pky">Tataniaga Post Border</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $key => $value)
                <tr>
                    <td class="tg-btxf">{{ $value['HSCD_DOCNO'] }}</td>
                    <td class="tg-btxf">{{ $value['HSCD_ITMCD'] }}</td>
                    <td class="tg-btxf">{{ $value['MITM_SPTNO'] }}</td>
                    <td class="tg-btxf">{{ $value['MITM_ITMD1'] }}</td>
                    <td class="tg-btxf">{{ $value['MITM_ITMD2'] }}</td>
                    <td class="tg-btxf">{{ $value['HSCD_MKHSCD'] }}</td>
                    <td class="tg-btxf">{{ $value['HSCD_STXICD'] }}</td>
                    <td class="tg-btxf">{{ $value['LIST_IMPORT'] }}</td>
                    <td class="tg-btxf">{!! $value['LIST_IMPORT_POST'] !!}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
