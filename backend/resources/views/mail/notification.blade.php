<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
</head>
<body style="font-family: sans-serif; color: #1f2937;">
    <h1 style="font-size: 20px;">Cuentas Claras</h1>
    <h2 style="font-size: 16px;">{{ $title }}</h2>
    <p>{{ $body }}</p>
    @if($link)
        <p><a href="{{ $link }}">Ver en Cuentas Claras</a></p>
    @endif
</body>
</html>
