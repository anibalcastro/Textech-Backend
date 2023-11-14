@php
    $contador = 0;
@endphp



<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">
    <title>Reporte de Clientes {{ ' ' . $fechaActual }}</title>
    <style>
        .encabezado {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .logo {
            width: 120px;
        }

        .descripcion {
            display: flex;
            align-items: flex-end;
            justify-content: flex-end;
            flex-direction: column;
            margin-top: -10px;
        }

        .titulo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
        <header>
            <div class="encabezado">
                <img class="logo" src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('Logojpg.jpg'))) }}" alt="Logo">
                <div class="descripcion text-right">
                    <p class="fecha">Fecha: <strong>{{ $fechaActual }}</strong></p>
                    <p class="descripcion">Reporte generado automáticamente</p>
                </div>
            </div>
        </header>

        <div class="titulo text-center">
            <h1 class="display-4">Reporte de Clientes</h1>
        </div>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Cédula</th>
                    <th>Teléfono</th>
                    <th>Empresa</th>
                    <th>Departamento</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($clientes as $item)
                    @php
                        $contador += 1;
                        $nombreCompleto = $item->nombre . ' ' . $item->apellido1 . ' ' . $item->apellido2;
                    @endphp

                    <tr>
                        <td>{{ $contador }}</td>
                        <td>{{ $nombreCompleto }}</td>
                        <td>{{ $item->cedula ?? '' }}</td>
                        <td>{{ $item->telefono ?? '' }}</td>
                        <td>{{ $item->empresa ?? '' }}</td>
                        <td>{{ $item->departamento ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
