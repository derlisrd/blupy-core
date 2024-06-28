<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a Blupy</title>
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
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            padding-top: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eeeeee;
            color: #eeeeee;
            background-color: #2102C7;
            border-radius: 8px;
        }
        .header img {
            max-width: 100px;
        }
        .content {
            padding: 20px 0;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #eeeeee;
            font-size: 12px;
            color: #888888;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin: 20px 0;
            font-size: 16px;
            color: #ffffff;
            background-color: #52D3D0;
            text-decoration: none;
            border-radius: 4px;
        }
        .button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{!! asset('assets/img/logo.png') !!}" alt="Blupy Logo">
            <h1>Bienvenido a Blupy</h1>
        </div>
        <div class="content">
            <p>Hola {{ $name ?? '' }}.</p>
            <p>Gracias por registrarte en Blupy. Estamos encantados de tenerte con nosotros.</p>
            <p>Blupy es tu plataforma de confianza para acceder a beneficios exclusivos con Punto Farma</p>
            <p>Para comenzar, puedes explorar nuestras funciones y servicios visitando nuestro sitio web.</p>
            <a href="https://blupy.com.py/" class="button">Explorar Blupy</a>
            <p>Si tienes alguna pregunta o necesitas ayuda, no dudes en contactarnos.</p>
            <p>Saludos,<br>El equipo de Blupy</p>
        </div>
        <div class="footer">
            <p>Blupy Digital © 2024. Todos los derechos reservados.</p>
            <p>Si no quieres recibir estos correos, puedes <a href="https://blupy.com.py/">darte de baja aquí</a>.</p>
        </div>
    </div>
</body>
</html>
