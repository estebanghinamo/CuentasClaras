<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Invitación a un workspace</title>
</head>
<body style="font-family: sans-serif; color: #1f2937;">
    <h1 style="font-size: 20px;">Cuentas Claras</h1>
    <p><strong>{{ $invitedByName }}</strong> te invitó a sumarte al workspace <strong>{{ $workspaceName }}</strong>.</p>
    <p><a href="{{ $link }}">{{ $link }}</a></p>
    <p>Si no esperabas esta invitación, podés ignorar este email.</p>
</body>
</html>
