<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Recuperá tu contraseña</title>
</head>
<body style="font-family: sans-serif; color: #1f2937;">
    <h1 style="font-size: 20px;">Cuentas Claras</h1>
    <p>Pediste recuperar tu contraseña. Hacé click en el siguiente enlace para elegir una nueva (vence en {{ config('cuentas.password_reset_ttl_minutes') }} minutos):</p>
    <p><a href="{{ $link }}">{{ $link }}</a></p>
    <p>Si no pediste esto, podés ignorar este email.</p>
</body>
</html>
