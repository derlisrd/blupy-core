<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            padding: 20px 0;
            background-color: #2102C7;
            color: white;
            border-radius: 10px 10px 0 0;
        }
        .content {
            padding: 20px;
            text-align: center;
        }
        .verification-code {
            font-size: 32px;
            font-weight: bold;
            color: #000;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 10px 0;
            color: #777777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{!! asset('assets/img/logo.png') !!}" alt="Blupy Logo">
            <h1>Verificación de dispositivo</h1>
        </div>
        <div class="content">
            <h3>Hemos detectado que se ha ingresado a tu cuenta de BLUPY, si fuiste tu utiliza este código</h3>
            <div class="verification-code"> {{ $code ?? '' }}</div>
            <p><strong>Detalles del intento:</strong></p>
            <p><strong>IP:</strong> {{ $ip ?? ''}}</p>
            <p><strong>Dispositivo:</strong> {{ $device ?? '' }}</p>
            <p>Si no fuiste tú, te recomendamos cambiar tu contraseña de inmediato y revisar la seguridad de tu cuenta.</p>
            <p>Gracias</p>
            <p>El equipo de BLUPY</p>
        </div>
        <div class="footer">
            <p>&copy; 2024 Blupy es un empresa registrada de Mi Credito S.A. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
