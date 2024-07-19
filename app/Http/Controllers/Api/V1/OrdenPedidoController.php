<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\OrdenPedido;
use App\Models\Facturas;

use Illuminate\Http\Request;
use App\Models\DetallePedido;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Archivos;
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
        } else {
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
    public function crearOrden(Request $request) {
        try {
            // Decodifica el JSON enviado en el cuerpo de la solicitud
            $data = json_decode($request->getContent(), true);

            // Valida los datos de la orden
            $validador = $this->validarDatosOrden($data['orden']);

            if ($validador !== true) {
                return response()->json([
                    'mensaje' => 'Los datos ingresados son incorrectos',
                    'error' => $validador,
                    'status' => 422 // Código de estado para datos no procesables
                ], 422);
            }

            // Iniciar una transacción de base de datos
            DB::beginTransaction();

            // Obtén los datos de la orden desde el JSON
            $orden = $data['orden'];
            $detalles = $orden['detalles'];
            $factura = $orden['factura'];
            $personas = $orden['persona'];

            // Crea la orden
            $crearOrden = OrdenPedido::create($orden);
            $idOrden = $crearOrden->id;

            // Crea los detalles de la orden
            $resultadoDetalles = $this->crearOrdenDetalle($detalles, $idOrden);

            if (!$resultadoDetalles) {
                DB::rollBack();
                return response()->json([
                    'mensaje' => 'Error al crear los detalles de la orden',
                    'status' => 500
                ], 500);
            }

            // Crea la factura asociada a la orden
            $objFactura = new Facturas();
            $objFactura->order_id = $idOrden;
            // Configura los campos de la factura con los datos correspondientes
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

            // Genera la factura utilizando el controlador correspondiente
            $facturaController = app(FacturasController::class);
            $resultadoFactura = $facturaController->generarFactura($objFactura);
            $dataFactura = $resultadoFactura->getData();

            if ($dataFactura->status !== 200) {
                DB::rollBack();
                return response()->json([
                    'mensaje' => 'Error al crear la factura',
                    'status' => 500
                ], 500);
            }

            // Asocia las personas a la orden
            $ordenPedidoPersonaController = app(OrdenPedidoPersonaController::class);
            $ordenPedidoPersonaController->registroOrdenPedidoPersona($personas, $idOrden);

            // Obtén el ID de la última factura generada
            $idFactura = $this->obtenerUltimoIdFactura();

            // Actualiza la orden con la referencia de la factura
            $ordenPedido = OrdenPedido::find($idOrden);
            $ordenPedido->id_factura = $idFactura;
            $ordenPedido->update();

            // Confirma la transacción si todo fue exitoso
            DB::commit();

            return response()->json([
                'mensaje' => 'Orden creada con éxito',
                'orden' => $orden,
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            // Si ocurre una excepción, revierte la transacción
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'mensaje' => 'Error al registrar la orden',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /*
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
    */

    public function crearOrdenDetalle($detalles, $idOrden){
        foreach ($detalles as $detalle) {
            $objDetalle = new DetallePedido();

            // Asegúrate de acceder a los elementos como índices de un array, no como propiedades de un objeto
            $objDetalle->id_pedido = $idOrden;
            $objDetalle->id_producto = $detalle['id_producto'];
            $objDetalle->precio_unitario = $detalle['precio_unitario'];
            $objDetalle->cantidad = $detalle['cantidad'];
            $objDetalle->descripcion = $detalle['descripcion'];
            $objDetalle->subtotal = $detalle['subtotal'];

            // Guarda el objeto DetallePedido en la base de datos
            $objDetalle->save();
        }

        // Retorna true si se completó con éxito
        return true;
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
            $data = json_decode($request->getContent(), true);

            $ordenArray = $data["orden"];
            $proforma = $ordenArray["proforma"];
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

            $orden->proforma = $proforma;

            // Guardar los cambios en la base de datos
            $orden->save();

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

            foreach ($detalles as $item) {
                // Verificar si el detalle existe en la base de datos
                $detalleExistente = $detallePedido->where('id', $item['id'])->first();

                if ($detalleExistente) {
                    // Actualizar el detalle existente
                    $resultado = $detalleExistente->update([
                        'id_producto' => $item['id_producto'],
                        'precio_unitario' => $item['precio_unitario'],
                        'cantidad' => $item['cantidad'],
                        'descripcion' => $item['descripcion'],
                        'subtotal' => $item['subtotal'],
                    ]);
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
            } elseif ($orden->estado === "Entrega Tienda") {
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
    public function cambiarEstadoPizarra($id_orden)
    {
        $orden = OrdenPedido::find($id_orden);

        if (!$orden) {
            return response()->json([
                'mensaje' => 'La orden no ha sido encontrada',
                'status' => 422
            ], 422);
        }

        OrdenPedido::where('id', $id_orden)->update(['pizarra' => true]);

        return response()->json([
            'mensaje' => 'Se ha actualizado correctamente',
            'status' => 200
        ], 200);
    }

    /**Cambia el estado de la tela */
    public function cambiarEstadoTelas($id_orden)
    {
        $orden = OrdenPedido::find($id_orden);

        if (!$orden) {
            return response()->json([
                'mensaje' => 'La orden no ha sido encontrada',
                'status' => 422
            ], 422);
        }

        OrdenPedido::where('id', $id_orden)->update(['tela' => true]);

        return response()->json([
            'mensaje' => 'Se ha actualizado correctamente',
            'status' => 200
        ], 200);
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



    public function getOrderFiles($orderId)
    {
        // Encuentra la orden por su ID
        $order = OrdenPedido::find($orderId);

        if ($order) {
            // Accede a los archivos asociados a la orden
            $archivos = $order->archivos;

            // Preparar las rutas de los archivos para enviar al frontend
            $rutasArchivos = $archivos->map(function ($archivo) {
                return [
                    'url' => asset('storage/archivos/' . $archivo->file_path),
                    'file_path' => $archivo->file_path
                ];
            });

            return response()->json(["data" => $rutasArchivos, "status" => 200]);
        } else {
            return response()->json(['error' => 'Orden no encontrada'], 404);
        }
    }

    /**
     * Se actualiza el entregado a true del detalle del pedido.
     * Se recibe la identificacion del detalle de la orden.
     */
    public function actualizarEstadoDetallePedido ($detalleId) {
        DetallePedido::where('id', $detalleId)->update(['entregado' => true]);
        return response()->json(['mensaje' => 'Modificado con exito' , 'status' => 200],200);
    }
}
