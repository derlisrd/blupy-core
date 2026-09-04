<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extracto Disponible</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
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
            color: #333333;
            line-height: 1.6;
        }
        .content p {
            margin: 12px 0;
        }
        .notice-box {
            background-color: #f8f9fa;
            border-left: 4px solid #2102C7;
            padding: 12px 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .disclaimer {
            font-size: 13px;
            color: #666666;
            font-style: italic;
            margin-top: 20px;
            border-top: 1px solid #eeeeee;
            padding-top: 10px;
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
            <h2>Tu extracto ya está disponible</h2>
        </div>
        <div class="content">
            <p>Hola <strong>{{ $nombre ?? '' }}</strong>,</p>

            <p>Te informamos que ya se encuentra disponible tu extracto correspondiente al periodo <strong>{{ $periodo ?? '' }}</strong> para que puedas consultarlo y realizar el pago de tu saldo adeudado.</p>

            <div class="notice-box">
                <p style="margin: 0;">📱 Puedes visualizar y descargar tu extracto ingresando directamente a la aplicación de <strong>BLUPY</strong>.</p>
            </div>

            <p class="disclaimer">
                <em>* Si ya realizaste el pago en las últimas horas, por favor ignora este mensaje.</em>
            </p>

            <p>Gracias,<br>El equipo de BLUPY</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} BLUPY. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>