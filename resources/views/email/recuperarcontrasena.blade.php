<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet" />
    <style>
        main{
            font-family:'Roboto', monospace;
            width: 480px;
            margin: 8px auto;
            padding: 8px;
            border:1px solid gray;
            border-radius: 8px;
            color:#000000;
        }
        .logo{
            width: 100%;
            padding:8px;
            margin: 0 auto;
        }
        .pie{
            padding:8px;
            width: 100%;
        }
        .text h1{
            font-size: 48px;
            color:#000000;
        }
        .text{text-align: center;}
        .text p{
            font-size: 12px;
        }
    </style>
</head>
<body style="max-width: 360px; width:360px; font-family: 'Roboto', monospace;  border-radios:8px;">
    <table width='400' >
        <tr>
            <td>
                <main>
                    <div class="logo">
                        <img width="128" src="{!! asset('assets/img/logo.png') !!}">
                    </div>
                    <div class="text">
                        <hr />
                        <h4>Utiliza este código para recuperar tu contraseña de Blupy</h4>
                        <hr />
                        <h1>{{ $code }}</h1>
                        <hr />
                        <p>Si no solicitaste este código ignora este mensaje.</p>
                    </div>
                    <div class="pie">
                    </div>
                </main>
            </td>
        </tr>
    </table>
</body>
</html>
