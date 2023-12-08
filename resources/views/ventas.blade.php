@php
    $contador = 0;
@endphp



<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <script src="{{ asset('js/bootstrap.js') }}"></script>


    <title>Reporte de ventas {{ ' ' . $fechaActual }}</title>
    <style>

        .navbar{
            margin-top: -35px;
        }

        .navbar-nav {
            width: 100%;
            list-style: none;
            padding-left: 0;
            margin-bottom: 0;
            text-align: right;
            margin-top: -80px;
            /* Alinea los elementos al final */
        }

        .navbar-nav li {
            display: block;
            margin-top: -20px;
            /* Hace que los elementos se muestren en línea */
        }

        .titulo{
            width: 100%;
            text-align: center;
        }

        .navbar>.container,
        .navbar>.container-fluid,
        .navbar>.container-lg,
        .navbar>.container-md,
        .navbar>.container-sm,
        .navbar>.container-xl,
        .navbar>.container-xxl {
            display: flex;
            flex-wrap: inherit;
            align-items: center;
            justify-content: space-between;
        }

        .container,
        .container-fluid,
        .container-lg,
        .container-md,
        .container-sm,
        .container-xl,
        .container-xxl {
            width: 100%;
            padding-right: var(--bs-gutter-x, .75rem);
            padding-left: var(--bs-gutter-x, .75rem);
            margin-right: auto;
            margin-left: auto;
        }

        body {
            background-color: white;
        }

        .logo {
            width: 120px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 15px;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container">
        <nav class="navbar navbar-inverse navbar-fixed-top">
            <div class="container-fluid">
                <div class="navbar-header">
                    <img class="logo"
                        src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('Logojpg.jpg'))) }}"
                        alt="Logo">
                </div>
                <ul class="nav navbar-nav">
                    <li>
                        <p class="fecha">Fecha: <strong>{{ $fechaActual }}</strong></p>
                    </li>
                    <li>
                        <p class="descripcion">Reporte generado automáticamente</p>
                    </li>
                    <li>
                        <strong><p class="descripcion">Reporte de ventas</p></strong>
                    </li>
                    <li>
                        <p class="fecha">Fecha Incio: <strong>{{ $fechaInicio }}</strong></p>
                    </li>
                    <li>
                        <p class="fecha">Fecha Final: <strong>{{ $fechaFinal }}</strong></p>
                    </li>
                    <li>
                        <p class="fecha">Total en ventas: <strong>{{ '¢' . number_format($totalVentas ?? 0, 2, ',', '.') }}</strong></p>
                    </li>
                    <li>
                        <p class="fecha">Total pagado: <strong>{{ '¢' . number_format($montoPagado ?? 0, 2, ',', '.') }}</strong></p>
                    </li>

                </ul>
            </div>
        </nav>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Monto facturado</th>
                    <th>Monto pagado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ventas as $item)
                    @php
                        $contador += 1;
                    @endphp

                    <tr>
                        <td>{{ $contador }}</td>
                        <td>{{ $item->fecha }}</td>
                        <td>{{ '¢' . number_format($item->monto_facturado ?? 0, 2, ',', '.') }}</td>
                        <td>{{ '¢' . number_format($item->monto_pagado ?? 0, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
