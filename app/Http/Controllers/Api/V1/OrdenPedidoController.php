<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\OrdenPedido;
use App\Models\Facturas;

use Illuminate\Http\Request;
use App\Models\DetallePedido;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class OrdenPedidoController extends Controller
{
    public function index()
    {
        $ordenesConDetalles = OrdenPedido::with('detalles')->latest()->get();

        return response()->json([
            'ordenes' => $ordenesConDetalles,
            'status' => 200,
        ]);
    }

    public function ordenPedidoDetalleFactura($id_orden)
    {
        $orden = OrdenPedido::with('detalles')->where('id', $id_orden)->first(); // Obtén la orden junto con los detalles

        if ($orden) {
            $ordenDetalles = $orden->detalles; // Accede a los detalles desde la relación
            $orden->unsetRelation('detalles'); // Quita la relación para separarla


            $idFactura = $orden->id_factura;

            $factura = Facturas::where('id', $idFactura)->get();

            return response()->json([
                'mensaje' => 'Orden encontrada con éxito',
                'orden' => $orden,
                'detalles' => $ordenDetalles,
                'facturas' => $factura,
                'status' => 200
            ]);
        }
        else{
            return response()->json([
                'mensaje' => 'Orden no encontrada',
                'status' => 422
            ]);
        }
    }

    /**
     * Get actual date.
     */
    public function obtenerFechaActual()
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Create an order, detail and invoice.
     */
    public function crearOrden(Request $request)
    {
        try {
            // Decodifica el JSON enviado en el cuerpo de la solicitud
            $data = json_decode($request->getContent(), true);

            // Valida los datos de la orden
            $validador = $this->validarDatosOrden($data['orden']);

            if ($validador === true) {
                // Iniciar una transacción de base de datos
                DB::beginTransaction();

                // Obtén la orden y los detalles del JSON
                $orden = $data['orden'];
                $detalles = $orden['detalles'];
                $factura = $orden['factura'];
                $persona = $orden['persona'];

                dd("Pasa los arrays");

                // Crea la orden
                $crearOrden = OrdenPedido::create($orden);

                // Guarda la orden y obtén su ID
                $idOrden = $crearOrden->id;

                // Crea los detalles de la orden
                $resultadoDetalles = $this->crearOrdenDetalle($detalles, $idOrden);

                if ($resultadoDetalles) {

                    $objFactura = new Facturas();
                    $objFactura->order_id = $idOrden;
                    $objFactura->reparacion_id = null;
                    $objFactura->empresa_id = $factura[0]['id_empresa'];
                    $objFactura->subtotal = $factura[0]['subtotal'];
                    $objFactura->iva = $factura[0]['iva'];
                    $objFactura->monto = $factura[0]['monto'];
                    $objFactura->fecha = $this->obtenerFechaActual();
                    $objFactura->metodo_pago = 'Pendiente';
                    $objFactura->saldo_restante = $factura[0]['saldo_restante'];
                    $objFactura->comentario = $factura[0]['comentario'];
                    $objFactura->estado = 'Activo';
                    $objFactura->cajero = $factura[0]['cajero'];

                    $facturaController = app(FacturasController::class);
                    $resultadoFactura = $facturaController->generarFactura($objFactura);

                    $data = $resultadoFactura->getData();

                    $ordenPedidoPersonaController = app(OrdenPedidoPersonaController::class);
                    $ordenPedidoPersonaController->registroOrdenPedidoPersona($persona, $idOrden);

                    if ($data->status === 200 ) {
                        // Confirma la transacción
                        DB::commit();

                        $idFactura = $this->obtenerUltimoIdFactura();

                        // Usar find para obtener la orden de pedido por ID
                        $ordenPedido = OrdenPedido::find($idOrden);

                        $ordenPedido->id_factura = $idFactura;

                        $resultado = $ordenPedido->update();

                        if ($resultado) {

                            return response()->json([
                                'mensaje' => 'Orden creada con éxito',
                                'orden' => $orden,
                                'status' => 200
                            ], 200);
                        } else {

                            // Si ocurre una excepción, revierte la transacción (si se inició)
                            if (DB::transactionLevel() > 0) {
                                DB::rollBack();
                            }

                            return response()->json([
                                'mensaje' => 'No se pudo obtener la ultima factura',
                                'status' => 500
                            ]);
                        }
                    }
                } else {
                    // Si ocurre un error, revierte la transacción
                    DB::rollBack();

                    return response()->json([
                        'mensaje' => 'Error al crear la orden de detalle',
                        'status' => 500
                    ], 500);
                }
            } else {
                return response()->json([
                    'mensaje' => 'Los datos ingresados son incorrectos',
                    'error' => $validador,
                    'status' => 422 // Código de estado para datos no procesables (Unprocessable Entity)
                ], 422);
            }
        } catch (\Exception $e) {
            // Si ocurre una excepción, revierte la transacción (si se inició)
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'dataOrden' => $data,
                'mensaje' => 'Error al registrar la orden',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Create an order detail
     */
    public function crearOrdenDetalle($detalles, $idOrden)
    {
        $detallePedidoValido = [];
        $detallePedidoIncorrecto = [];
        $contadorAgregados = 0;



        foreach ($detalles as $detalle) {
            $detalle["id_pedido"] = $idOrden; // Asigna el id del pedido al detalle
            $validadorDatos = $this->validarDatosDetalle($detalle);

            if ($validadorDatos === true) {
                $detallePedidoValido[] = $detalle;
            } else {
                $detallePedidoIncorrecto[] = $detalle;
            }
        }

        if (count($detallePedidoIncorrecto) == 0) {
            foreach ($detallePedidoValido as $detalle) {
                // Usar transacción para asegurar que todas las operaciones sean exitosas
                DB::transaction(function () use ($detalle, &$contadorAgregados) {
                    $crearDetallePedido = DetallePedido::create($detalle);
                    if ($crearDetallePedido->save()) {
                        $contadorAgregados++;
                    }
                });
            }

            if ($contadorAgregados == count($detallePedidoValido)) {
                return true;
            } else {
                return false; // No se agregaron correctamente todos los detalles válidos
            } // Retornar false si no se agregaron correctamente todos los detalles válidos
        } else {
            return ['detalleIncorrecto' => $detallePedidoIncorrecto, 'resultado' => false];
        }
    }

    /**
     * Get a latest id of an invoice.
     */
    public function obtenerUltimoIdFactura()
    {
        $ultimoId = Facturas::max('id');
        return $ultimoId;
    }

    /**
     * Modify an specific order
     */
    public function modificarOrden(Request $request, $id_orden)
    {
        try {
            //code...
            //get data
            $data = json_decode($request->getContent(), true);

            $ordenArray = $data["orden"];
            $detalles = $ordenArray["detalles"];

            //Get a order...
            $orden = OrdenPedido::find($id_orden);

            //Validate if no exist order...
            if (!$orden) {
                //Return response error...
                return response()->json([
                    "mensaje" => "Error, no se ha podido encontrar la orden",
                    "status" => 404
                ], 404);
            }

            $facturaController = app(FacturasController::class);
            $nuevoMonto = $data["orden"]["factura"][0]["monto"];
            $nuevoSubtotal = $data["orden"]["factura"][0]['subtotal'];
            $nuevoIva = $data["orden"]["factura"][0]['iva'];

            //Id orden, monto, subtotal, iva
            $modificacionFactura = $facturaController->modificarFactura($id_orden, $nuevoMonto, $nuevoSubtotal, $nuevoIva, "orden_id");


            $resultadoFactura = $modificacionFactura->getData();

            if ($resultadoFactura->status === 200) {
                //Result of the function ${modificarOrdenDetalle}
                $modificacionDetalle = $this->modificarOrdenDetalle($detalles, $id_orden); // Pasar el json Request

                $resultadoDetalle = $modificacionDetalle->getData();

                //dd($resultadoDetalle);

                //If result is true
                if ($resultadoDetalle->status === 200) {

                    $nuevoMontoTotal = $resultadoDetalle->nuevoMonto;
                    $orden->update(["monto" => $nuevoMontoTotal]);

                    return response()->json([
                        "mensaje" => "Orden de pedido actualizada",
                        "nuevoMonto" => $nuevoMontoTotal,
                        "status" => 200,
                    ]);
                } else {
                    return response()->json([
                        "mensaje" => "Error al modificar el detalle",
                        "errores" => $resultadoDetalle->error,
                        "status" => 400
                    ]);
                }
            } else {
                return response()->json([
                    'mensaje' => $resultadoFactura->mensaje,
                    'status' => 422
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error, no se ha podido modificar la orden',
                'error' => $e->getMessage(),
                'status' => 422
            ]);
        }
    }

    public function modificarOrdenDetalle($detalles, $id_orden)
    {
        try {
            $modificacionCorrecta = [];
            $nuevoMonto = 0;

            // Obtener los detalles de pedido existentes para el pedido en cuestión
            $detallePedido = DetallePedido::where('id_pedido', $id_orden)->get();

            // Crear un arreglo para realizar un seguimiento de los detalles existentes que no se actualizarán
            $detallesNoActualizados = $detallePedido->toArray();

            foreach ($detalles as $item) {
                // Verificar si el detalle existe en la base de datos
                $detalleExistente = $detallePedido->where('id_producto', $item['id_producto'])
                    ->where('descripcion', $item['descripcion'])
                    ->first();

                if ($detalleExistente) {
                    // Actualizar el detalle existente
                    $resultado = $detalleExistente->update([
                        'precio_unitario' => $item['precio_unitario'],
                        'cantidad' => $item['cantidad'],
                        'subtotal' => $item['subtotal'],
                    ]);

                    if ($resultado) {
                        $nuevoMonto += $item['subtotal'];
                        $modificacionCorrecta[] = $item;
                        // Eliminar el detalle existente del arreglo de no actualizados
                        $detallesNoActualizados = array_filter($detallesNoActualizados, function ($detalle) use ($item) {
                            return !($detalle['id_producto'] === $item['id_producto'] && $detalle['descripcion'] === $item['descripcion']);
                        });
                    }
                } else {
                    // Si el detalle no existe en la base de datos, agregarlo como un nuevo detalle
                    $detalleNuevo = new DetallePedido([
                        'id_pedido' => $id_orden,
                        'id_producto' => $item['id_producto'],
                        'descripcion' => $item['descripcion'],
                        'precio_unitario' => $item['precio_unitario'],
                        'cantidad' => $item['cantidad'],
                        'subtotal' => $item['subtotal'],
                    ]);

                    $resultado = $detalleNuevo->save();

                    if ($resultado) {
                        $nuevoMonto += $item['subtotal'];
                        $modificacionCorrecta[] = $item;
                    }
                }
            }

            // Eliminar detalles existentes que no se actualizaron (productos eliminados)
            foreach ($detallesNoActualizados as $detalleNoActualizado) {
                $detalleExistente = $detallePedido->where('id_producto', $detalleNoActualizado['id_producto'])
                    ->where('descripcion', $detalleNoActualizado['descripcion'])
                    ->first();

                if ($detalleExistente) {
                    $detalleExistente->delete();
                }
            }

            // Actualizar el monto total en la tabla de pedidos
            $orden = OrdenPedido::find($id_orden);
            $orden->update(['precio_total' => $nuevoMonto]);

            return response()->json([
                "mensaje" => "Orden de pedido actualizada",
                "nuevoMonto" => $nuevoMonto,
                "status" => 200,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'mensaje' => 'Error al modificar la orden',
                'error' => $e->getMessage(),
            ]);
        }
    }


    /**
     * Función para actualizar el estado del pedido.
     */
    public function actualizarEstadoPedido(Request $request, $id_orden)
    {
        //Estados
        $estados = ["Taller", "Entrega Tienda", "Entregada al cliente"];

        $nuevoEstado = $request->estado;
        $orden = OrdenPedido::find($id_orden);



        if ($orden) {
            if (in_array($nuevoEstado, $estados)) {

                $estadoActual = $orden->estado; //Estado actual de la orden
                $posicionEstadoActual = array_search($estadoActual, $estados); //Posicion del estado actual en $estados
                $posicionEstadoNuevo = array_search($nuevoEstado, $estados); //Posicion del nuevo estado


                if ($posicionEstadoNuevo > $posicionEstadoActual) {
                    OrdenPedido::where('id', $id_orden)->update(['estado' => $nuevoEstado]); //Si la posicion es mayor al estado actual, se actualiza el estado.

                    return response()->json([
                        "mensaje" => "Estado actualizado con exito",
                        "status" => 200,
                        "nuevoEstado" => $nuevoEstado,
                        "estadoAnterior" => $estadoActual
                    ], 200);
                } else {
                    return response()->json([
                        "mensaje" => "El estado no es correcto",
                        "status" => 422
                    ], 422);
                }
            } else {
                return response()->json([
                    "mensaje" => "La nueva posición no puede estar antes de la posición actual.",
                    "status" => 500
                ], 500);
            }
        }

        return response()->json([
            "mensaje" => "Error, no se ha podido encontrar la orden",
            "status" => 404
        ], 404);
    }

    /**
     * Función para eliminar la orden de pedido, a la vez se elimina el detalle de ese pedido.
     */
    public function anularOrden($id_orden)
    {
        try {
            $orden = OrdenPedido::find($id_orden);

            if ($orden) {

                $orden->estado = 'Anulada';
                $consecutivo = $orden->id_factura;

                $resultado = $orden->update();

                if ($resultado) {
                    //Se anula la factura también
                    $facturaController = app(FacturasController::class);
                    $resultadoFactura = $facturaController->anularFactura($consecutivo);

                    //Anular los abonos...
                    $abonosController = app(AbonosController::class);
                    $resultadoAbono = $abonosController->anularAbonoPorIdFactura($consecutivo);

                    $contentFactura = $resultadoFactura->getData();
                    $contentAbono = $resultadoAbono->getData();
                    if ($contentFactura->status === 200 && $contentAbono->status === 200 || $contentAbono->status === 422) {
                        return response()->json([
                            'mensaje' => 'Orden anulada, factura y abonos correspondientes tambien han sido anulados.',
                            'status' => 200
                        ], 200);
                    }
                }

                return response()->json([
                    "mensaje" => "Orden de pedido y detalle anulada de manera correcta",
                    "status" => 200
                ], 200);
            }

            return response()->json([
                "mensaje" => "Error, no se ha podido encontrar la orden",
                "status" => 404
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error',
                'error' => $e->getMessage(),
                'status' => 422
            ]);
        }
    }

    /**
     * Función que retorna cantidad de pedidos según el estado
     */
    public function cantidadOrdenEstado()
    {
        $ordenes = OrdenPedido::all();
        $cantidad_taller = 0;
        $cantidad_entrega_tienda = 0;
        $cantidad_entrega_cliente = 0;

        foreach ($ordenes as $orden) {
            if ($orden->estado === "Taller") {
                $cantidad_taller++;
            } elseif ($orden->estado === "Entrega tienda") {
                $cantidad_entrega_tienda++;
            } elseif ($orden->estado === "Entregada al cliente") {
                $cantidad_entrega_cliente++;
            }
        }

        return response()->json([
            "cantidad_taller" => $cantidad_taller,
            "cantidad_entrega_tienda" => $cantidad_entrega_tienda,
            "cantidad_entrega_cliente" => $cantidad_entrega_cliente,
            "status" => 200
        ], 200);
    }

    /**Cambia el estado de la pizarra */
    public function cambiarEstadoPizarra($id_orden){
        $orden = OrdenPedido::find($id_orden);

        if (!$orden){
            return response()->json([
                'mensaje' => 'La orden no ha sido encontrada',
                'status' => 422
            ],422);
        }

        OrdenPedido::where('id', $id_orden)->update(['pizarra' => true]);

        return response()->json([
            'mensaje' => 'Se ha actualizado correctamente',
            'status' => 200
        ],200);



    }

    /**Cambia el estado de la tela */
    public function cambiarEstadoTelas($id_orden){
        $orden = OrdenPedido::find($id_orden);

        if (!$orden){
            return response()->json([
                'mensaje' => 'La orden no ha sido encontrada',
                'status' => 422
            ],422);
        }

        OrdenPedido::where('id', $id_orden)->update(['tela' => true]);

        return response()->json([
            'mensaje' => 'Se ha actualizado correctamente',
            'status' => 200
        ],200);

    }


    /**
     * Función para validar los datos de entrada de la orden.
     */
    public function validarDatosOrden($request)
    {
        $reglas = [
            "id_empresa" => ['required', 'integer', 'exists:empresas,id'],
            "titulo" => ['required', 'string', "max:100"],
            "fecha_orden" => ['required', 'date'],
            'precio_total' => ['required', 'numeric', 'regex:/^\d+(\.\d{1,2})?$/'],
            'estado' => ['required', 'string']
        ];

        $mensajes = [
            'id_empresa.required' => 'El campo ID de empresa es obligatorio.',
            'id_empresa.integer' => 'El campo ID de empresa debe ser un número entero.',
            'id_empresa.exists' => 'El ID de empresa no existe en la tabla de empresas.',

            'titulo.required' => 'El campo estado es obligatorio.',
            'titulo.string' => 'El campo estado debe ser una cadena de texto.',

            'fecha_orden.required' => 'El campo fecha de orden es obligatorio.',
            'fecha_orden.date' => 'El campo fecha de orden debe ser una fecha válida.',

            'precio_total.required' => 'El campo precio total es obligatorio.',
            'precio_total.numeric' => 'El campo precio total debe ser un número.',
            'precio_total.regex' => 'El formato del precio total es inválido. Debe ser un número decimal con hasta dos decimales.',

            'estado.required' => 'El campo estado es obligatorio.',
            'estado.string' => 'El campo estado debe ser una cadena de texto.'
        ];

        $validador = Validator::make($request, $reglas, $mensajes);

        if ($validador->fails()) {
            return $validador->errors()->all();
        }

        return true;
    }

    /**
     * Función para validar datos de entrada en la orden detalle.
     */
    public function validarDatosDetalle($request)
    {
        $reglas = [
            'id_pedido' => 'required',
            'id_producto' => 'required|exists:productos,id',
            'precio_unitario' => 'required|numeric|min:0',
            'cantidad' => 'required|integer|min:1',
            'descripcion' => 'required|string|max:255',
            'subtotal' => 'required|numeric|min:0'
        ];

        $mensajes = [
            'id_pedido.required' => 'El campo de id de pedido es requerido.',
            'id_producto.required' => 'El campo de id de producto es requerido.',
            'precio_unitario.required' => 'El campo de precio unitario es requerido.',
            'precio_unitario.numeric' => 'El precio unitario debe ser un valor numérico.',
            'precio_unitario.min' => 'El precio unitario debe ser al menos :min.',
            'cantidad.required' => 'El campo de cantidad es requerido.',
            'cantidad.integer' => 'La cantidad debe ser un valor entero.',
            'cantidad.min' => 'La cantidad debe ser al menos :min.',
            'descripcion.required' => 'El campo de descripción es requerido.',
            'descripcion.string' => 'La descripción debe ser un texto válido.',
            'descripcion.max' => 'La descripción no debe exceder :max caracteres.',
            'subtotal.required' => 'El campo de subtotal es requerido.',
            'subtotal.numeric' => 'El subtotal debe ser un valor numérico.',
            'subtotal.min' => 'El subtotal debe ser al menos :min.',
            'id_pedido.exists' => 'El id de pedido no existe en la tabla orden_pedido.',
            'id_producto.exists' => 'El id de producto no existe en la tabla productos.',
        ];

        $validador = Validator::make($request, $reglas, $mensajes);

        if ($validador->fails()) {
            return $validador->errors()->all();
        }

        return true;
    }
}
