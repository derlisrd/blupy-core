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
            font-size: 24px;
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
            <h1>Verificación de Email</h1>
        </div>
        <div class="content">
            <p>Por favor, usa el siguiente código para verificar tu dirección de email:</p>
            <div class="verification-code"> {{ $code }}</div>
            <p>Si no solicitaste esta verificación, por favor ignora este mensaje.</p>
        </div>
        <div class="footer">
            <p>&copy; 2024 Blupy es un empresa registrada de Mi Credito S.A. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
