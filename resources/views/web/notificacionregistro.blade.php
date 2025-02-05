<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blupy</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }

        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .logo-container {
            margin-bottom: 20px;
        }

        .logo {
            max-width: 150px;
            height: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .separator {
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="https://blupy.com.py/assets/assets/img/logo.51c282b1e7feee8167c1fe4015be9879.png" alt="Blupy Logo" class="logo" />
        </div>
        <table>
            <tr>
                <th>CEDULA</th>
                <th>NOMBRE</th>
                <th>APELLIDO</th>
            </tr>
            <tr>
                <td>{{ $cedula ?? '' }}</td>
                <td>{{ $nombres ?? '' }}</td>
                <td>{{ $apellidos ?? '' }}</td>
            </tr>
            <tr>
                <td colspan="3" class="separator"></td>
            </tr>
        </table>
    </div>
</body>
</html>
