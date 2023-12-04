<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación Textec</title>
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <script src="{{ asset('js/bootstrap.js') }}"></script>

    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            /* Cambiado de 100vh a 100% */
        }

        .container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        p {
            font-size: 16px;
            line-height: 1.6;
            color: #333333;
        }

        .cuerpo {
            text-align: justify;
        }


        footer {
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .clogo{
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo {
            margin-top: 20px;
            max-width: 150px;
            height: auto;
        }
    </style>
</head>

<body>
    <div class="container">
        <p class="cuerpo">{{ $contenidoCorreo }}</p>
    </div>
    <footer>
        <p>Por favor no responda este correo, ya que fue generado automáticamente</p>
        <div class="clogo">
            <img class="logo" src="https://api.textechsolutionscr.com/Logojpg.jpg" alt="Logo" />
        </div>
    </footer>
</body>

</html>
