<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div style="background-color: white;">
        @foreach ($data as $key => $value)
            {!! $value['cfm_content'] !!}
            <div style="page-break-before:always"></div>
        @endforeach
    </div>
</body>
</html>
