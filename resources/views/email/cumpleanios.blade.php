<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feliz Cumpleaños</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            border: 1px solid #ccc;
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
            color: #333333;
        }
        .greeting {
            font-size: 32px;
            font-weight: bold;
            color: #2102C7;
            margin: 20px 0;
        }
        .message {
            font-size: 18px;
            color: #555555;
            margin: 20px 0;
        }
        .discount {
            font-size: 24px;
            color: #d9534f;
            font-weight: bold;
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
            <h1>¡Feliz Cumpleaños, {{ $nombre ?? '' }}!</h1>
        </div>
        <div class="content">
            <h3 class="greeting">🎉 ¡Que tengas un día maravilloso! 🎉</h3>
            <p class="message">En Blupy, queremos desearte un feliz cumpleaños y agradecerte por ser parte de nuestra familia.</p>
            <p class="discount">¡Por tu Cumpleaños tenes un 30% de descuento en tus compras de hoy!</p>
            <p>Gracias por confiar en nosotros. ¡Esperamos que tengas un día lleno de alegría y sorpresas!</p>
            <p>Con cariño,</p>
            <p>El equipo de BLUPY</p>
        </div>
        <div class="footer">
            <p>&copy; 2024 Blupy es una empresa registrada de Mi Credito S.A. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
