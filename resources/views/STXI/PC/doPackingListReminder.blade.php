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
    <h1 style="text-align: left;">Dear PC,</h1>
    <p>Please kindly proceed input PO & Sales Invoice for DO SME as below</p>

    <table class="tg">
        <thead>
            <tr>
                <th class="tg-0pky">BG</th>
                <th class="tg-0pky">DO</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $key => $value)
                <tr>
                    <td class="tg-btxf">{{$value['KSHP_BSGRP']}}</td>
                    <td class="tg-btxf">{{$value['KSHP_DONO']}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="top-padding:1em">
        * This is an automatically generated email. Do not reply.
    </div>
</div>
