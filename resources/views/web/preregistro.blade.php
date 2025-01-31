<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de registro</title>
    <style>
        /* Estilos generales */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        /* Contenedor del formulario */
        .form-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            width: 360px;
            text-align: center;
        }

        .form-container form {
            display: flex;
            align-items: center;
            flex-direction: column;
            gap: 8px;
        }

        /* Campos de entrada */
        .form-container input {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s ease-in-out;
        }

        /* Efecto al enfocar */
        .form-container input:focus {
            border-color: #007bff;
            outline: none;
        }

        /* Botón */
        .form-container button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease-in-out;
        }

        /* Efecto hover en botón */
        .form-container button:hover {
            background-color: #0056b3;
        }

        .success-message {
            color: green;
            margin-top: 10px;
        }

        .error-message {
            color: red;
            margin-top: 10px;
        }
        .logo-container{
            padding: 8px;
            border-radius: 12px;
            background: #0056b3;
            display:flex;
            justify-content: center;
        }
        .logo {
            width: 150px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="form-container">

        <div class="logo-container">
            <img src="https://blupy.com.py/assets/assets/img/logo.51c282b1e7feee8167c1fe4015be9879.png" alt="Blupy Logo"
            class="logo" />
        </div>
        <h2>Formulario de registro</h2>
        <!-- Mostrar mensajes de éxito o error -->
        @if (session('success'))
            <p class="success-message">{{ session('success') }}</p>
        @endif

        @if ($errors->any())
            <p class="error-message">{{ $errors->first() }}</p>
        @endif
        <form action="{{ route('pre-registro.store') }}" method="POST">
            @csrf
            <input type="text" placeholder="Cédula" name="cedula" />
            <input type="text" placeholder="Nombres" name="nombres" />
            <input type="text" placeholder="Apellidos" name="apellidos" />
            <button type="submit">Enviar</button>
        </form>


    </div>

</body>

</html>
