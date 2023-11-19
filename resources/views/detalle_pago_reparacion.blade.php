@php
    $contador = 0;
    $contadorPagos = 0;
@endphp



<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <script src="{{ asset('js/bootstrap.js') }}"></script>


    <title>Detalle de la reparación</title>
    <style>
        .agradecimiento{
            width: 100%;
            text-align: center;
        }

        .navbar-header {
            float: right;
            margin-right: 5px;
            /* Espacio entre el logotipo y el borde derecho */
        }


        .navbar {
            margin-top: -10px;
        }

        .navbar-nav {
            width: 100%;
            list-style: none;
            padding-left: 0;
            margin-bottom: 0;
            text-align: left;
            /* Alinea los elementos al final */
            margin-top: 20px;
        }

        .navbar-nav li {
            display: block;
            margin-top: -20px;
            /* Hace que los elementos se muestren en línea */
        }


        body {
            background-color: white;
        }

        .logo {
            width: 200px;
            height: auto;
            text-align: right;
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

        .linea {
            height: 0.01px;
            background-color: rgb(191, 189, 189);
            width: 100%;
            margin-top: 10px;
            margin-bottom: 10px;
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
            </div>
        </nav>


        <div>
            <ul class="nav navbar-nav">
                <li>
                    <p class="descripcion">Consecutivo: #<strong>{{ $encabezadoPedido->id }}</strong></p>
                </li>
                <li>
                    <p class="descripcion">Fecha: <strong>{{ $encabezadoPedido->fecha }}</strong></p>
                </li>

                <li>
                    <p class="descripcion">Titulo: <strong>{{ $encabezadoPedido->titulo }}</strong></p>
                </li>
                <li>
                    <p class="descripcion">Empresa: <strong>{{ $encabezadoPedido->nombre_empresa }}</strong></p>
                </li>
                <li>
                    <p class="descripcion">Tel. Encargado: <strong>{{ $encabezadoPedido->telefono_encargado }}</strong>
                    </p>
                </li>
                <li>
                    <p class="descripcion">Estado del pedido: <strong>{{ $encabezadoPedido->estado }}</strong></p>
                </li>
                <li>
                    <p class="descripcion">Vendedor: <strong>{{ $encabezadoPedido->cajero }}</strong></p>
                </li>


            </ul>
        </div>

        <div class="linea"> </div>

        <h3>Detalle de la orden</h4>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Producto</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Precio unitario</th>
                        <th>Precio Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($detalle as $item)
                        @php
                            $contador += 1;
                        @endphp

                        <tr>
                            <td>{{ $contador }}</td>
                            <td>{{ $item->nombre_producto ?? '' }}</td>
                            <td>{{ $item->descripcion ?? '' }}</td>
                            <td>{{ $item->cantidad ?? '' }}</td>
                            <td>{{ '¢' . number_format($item->precio_unitario ?? 0, 2, ',', '.') }}</td>
                            <td>{{ '¢' . number_format((float) ($item->subtotal ?? 0), 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="linea"> </div>


            <h3>Facturación</h4>

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Subtotal</th>
                            <th>IVA 13%</th>
                            <th>Total</th>
                            <th>Monto Pendiente</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ '¢' . number_format($factura->subtotal ?? '', 2, ',', '.') }}</td>
                            <td>{{ '¢' . number_format($factura->iva ?? '', 2, ',', '.') }}</td>
                            <td>{{ '¢' . number_format($factura->monto ?? '', 2, ',', '.') }}</td>
                            <td>{{ '¢' . number_format($factura->saldo_restante ?? '', 2, ',', '.') }}</td>
                        </tr>

                    </tbody>
                </table>

                <div class="linea"> </div>

                <h3>Pagos</h4>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Método de pago</th>
                                <th>Monto</th>
                                <th>Cajero</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pagos as $item)
                                @php
                                    $contadorPagos += 1;
                                @endphp

                                <tr>
                                    <td>{{ $contadorPagos }}</td>
                                    <td>{{ $item->created_at ?? '' }}</td>
                                    <td>{{ $item->estado ?? '' }}</td>
                                    <td>{{ $item->metodo_pago ?? '' }}</td>
                                    <td>{{ '¢' . number_format($item->monto ?? 0, 2, ',', '.') }}</td>
                                    <td>{{ $item->cajero ?? '' }}</td>

                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="linea"> </div>
                    <p class="agradecimiento">Muchas gracias por su preferencia.</p>
    </div>
</body>

</html>
