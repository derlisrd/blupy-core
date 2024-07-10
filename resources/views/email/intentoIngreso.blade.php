<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intento de Ingreso</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #2102C7;
            color: #ffffff;
            padding: 10px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            padding: 20px;
        }
        .content p {
            margin: 10px 0;
        }
        .footer {
            background-color: #f4f4f4;
            color: #555555;
            text-align: center;
            padding: 10px;
            border-radius: 0 0 10px 10px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Intento de Ingreso a tu cuenta de BLUPY</h2>
        </div>
        <div class="content">
            <p>Hola {{ $nombre ?? '' }}</p>
            <p>Hemos detectado el ingreso a tu cuenta desde un dispositivo inusual.</p>
            <p><strong>Detalles del intento:</strong></p>
            <p><strong>IP:</strong> {{ $ip ?? ''}}</p>
            <p><strong>Dispositivo:</strong> {{ $device ?? '' }}</p>
            <p>Si no fuiste tú, te recomendamos cambiar tu contraseña de inmediato y revisar la seguridad de tu cuenta.</p>
            <p>Gracias</p>
            <p>El equipo de BLUPY</p>
        </div>
        <div class="footer">
            <p>&copy; 2024 BLUPY. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>