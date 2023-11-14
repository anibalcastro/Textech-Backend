
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario</title>
    <style>

        body {
            font-family: Arial, sans-serif;
            margin: 15px, 15px, 15px, 15px
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }

        .logo{
            width: 115px;
            align-self: flex-start;
        }

        .encabezado{
            display: flex;
            align-items: flex-end;
            justify-content: flex-end;
            flex-direction: column;
        }

        .titulo{
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
        }

        .fecha, .descripcion {
            margin-top: -57px;
            color: gray;
        }
    </style>
</head>
<body>

    <header>
        <div class="encabezado">
            @php
                // Obtener la fecha actual utilizando Carbon
                $fechaActual = \Carbon\Carbon::now();
            @endphp

            <img class="logo" src="/Logo.webp" alt="Logo">

            <hr>
            <p class="fecha">Fecha: {{ $fechaActual->toDateString() }}</p>
            <p class="descripcion">Reporte generado automáticamente</p>


        </div>
    </header>

    <div class="titulo">
        <h1>Inventario Actual</h1>
    </div>


    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Color</th>
                <th>Categoría</th>
                <th>Proveedor</th>
                <th>Comentario</th>
            </tr>
        </thead>
        <tbody>
            @foreach($inventario as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->nombre_producto }}</td>
                    <td>{{ $item->cantidad }}</td>
                    <td>{{ $item->color }}</td>
                    <td>{{ $item->categoria->nombre_categoria ?? '' }}</td>
                    <td>{{ $item->proveedor->nombre ?? '' }}</td>
                    <td>{{ $item->comentario }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
