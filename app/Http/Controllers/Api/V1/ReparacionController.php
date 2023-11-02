<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\AbonosController;
use App\Http\Controllers\Api\V1\FacturasController;
use App\Models\ReparacionPrendas;
use App\Models\DetalleReparacionPrendas;
use App\Models\Facturas;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ReparacionController extends Controller
{

    /**Método para observar todas las reparacion con detalles */
    public function index()
    {
        $reparacionConDetalles = ReparacionPrendas::with('detalleReparacion')->latest()->get();

        return response()->json([
            'reparaciones' => $reparacionConDetalles,
            'status' => 200,
        ]);
    }

    /**Metodo que retorna la reparacion de la factura */
    public function reparacionDetalleFactura($id_reparacion)
    {
        $reparacion = ReparacionPrendas::with('detalleReparacion')->where('id', $id_reparacion)->first();

        if ($reparacion) {
            $reparacion->unsetRelation('detalle_reparacion');

            $id_factura = $reparacion->id_factura;

            $factura = Facturas::where('id', $id_factura)->get();

            return response()->json([
                'mensaje' => 'Reparacion encontrada con exito',
                'reparacion' => $reparacion,
                'factura' => $factura,
                'status' => 200
            ], 200);
        } else {
            return response()->json([
                'mensaje' => 'Reparacion no encontrada',
                'status' => 422
            ], 422);
        }
    }

    /**Metodo que retorna la fecha actual*/
    public function fechaActual()
    {
        return date('Y-m-d H:i:s');
    }

    /**Metodo para crear reparacion */
    public function crearReparacion(Request $request)
    {
        try {
            $data = json_decode($request->getContent(), true);

            $validador = $this->validarDatosReparacion($data['reparacion']);

            if ($validador === true) {
                DB::beginTransaction();

                $reparacion = $data['reparacion'];
                $detalles = $reparacion['detalles'];
                $factura = $reparacion['factura'];

                $crearReparacion = ReparacionPrendas::create($reparacion);

                $id_reparacion = $crearReparacion->id;

                $resultadoDetalles = $this->crearDetalleReparacion($detalles, $id_reparacion);

                if ($resultadoDetalles === true) {

                    $objFactura = new Facturas();
                    $objFactura->order_id = null;
                    $objFactura->reparacion_id = $id_reparacion;
                    $objFactura->empresa_id = $factura[0]['id_empresa'];
                    $objFactura->subtotal = $factura[0]['subtotal'];
                    $objFactura->iva = $factura[0]['iva'];
                    $objFactura->monto = $factura[0]['monto'];
                    $objFactura->fecha = $this->fechaActual();
                    $objFactura->metodo_pago = 'Pendiente';
                    $objFactura->saldo_restante = $factura[0]['saldo_restante'];
                    $objFactura->comentario = $factura[0]['comentario'];
                    $objFactura->estado = 'Activo';
                    $objFactura->cajero = $factura[0]['cajero'];

                    $facturaController = app(FacturasController::class);

                    $resultadoFactura = $facturaController->generarFactura($objFactura);

                    $data = $resultadoFactura->getData();

                    if ($data->status === 200) {
                        DB::commit();

                        $id_factura = $this->obtenerUltimoIdFactura();

                        $reparaciones = ReparacionPrendas::find($id_reparacion);

                        $reparaciones->id_factura = $id_factura;
                        $resultado = $reparaciones->update();


                        if ($resultado) {
                            return response()->json([
                                'mensaje' => 'Reparacion creada con exito',
                                'status' => 200
                            ], 200);
                        } else {
                            if (DB::transactionLevel() > 0) {
                                DB::rollBack();
                            }

                            return response()->json([
                                'mensaje' =>  'No se pudo obtener la ultima factura',
                                'status' => 422
                            ], 422);
                        }
                    } else {
                        // Si ocurre un error, revierte la transacción
                        DB::rollBack();

                        return response()->json([
                            'mensaje' => 'Error al crear el detalle de la reparacion',
                            'status' => 500
                        ], 500);
                    }
                }
                else{
                    return response()->json([
                        'mensaje' => 'Los datos ingresados son incorrectos',
                        'error' => $validador,
                        'status' => 422 // Código de estado para datos no procesables (Unprocessable Entity)
                    ], 422);
                }
            }
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'dataOrden' => $data,
                'mensaje' => 'Error al registrar la reparacion',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**Metodo para crear detalle de reparacion */
    public function crearDetalleReparacion($detalles, $id_reparacion)
    {

        $detalleValido = [];
        $detalleIncorrecto = [];
        $contadorAgregados = 0;

        foreach ($detalles as $detalle) {
            $detalle['id_reparacion'] = $id_reparacion;
            $validadorDatos = $this->validarDatosDetalle($detalle);

            if ($validadorDatos === true) {
                $detalleValido[] = $detalle;
            } else {
                $detalleIncorrecto[] = $detalle;
            }
        }

        if (count($detalleIncorrecto) == 0) {
            foreach ($detalleValido as $detalle) {

                DB::transaction(function () use ($detalle, &$contadorAgregados) {
                    $crearDetalleReparacion = DetalleReparacionPrendas::create($detalle);

                    if ($crearDetalleReparacion->save()) {
                        $contadorAgregados++;
                    }
                });
            }


            if ($contadorAgregados == count($detalleValido)) {
                return true;
            } else {
                return false; // No se agregaron correctamente todos los detalles válidos
            } // Retornar false si no se agregaron correctamente todos los detalles válidos
        } else {
            return ['detalleIncorrecto' => $detalleIncorrecto, 'resultado' => false];
        }
    }

    /**Metodo para obtener el id de la ultima factura registrada */
    public function obtenerUltimoIdFactura()
    {
        return Facturas::max('id');
    }

    /**Metodo para modificar una reparacion */
    public function modificarReparacion(Request $request, $id_reparacion)
    {
        try {
            $data = json_decode($request->getContent(), true);

            $reparacionArray = $data['reparacion'];
            $detalles = $reparacionArray['detalles'];

            $reparacion = ReparacionPrendas::find($id_reparacion);

            if (!$reparacion) {
                return response()->json([
                    "mensaje" => "Error, no se ha podido encontrar la reparacion",
                    "status" => 404
                ], 404);
            }

            $facturaController = app(FacturasController::class);
            $nuevoMonto = $data['reparacion']['factura'][0]['monto'];
            $nuevoSubtotal = $data['reparacion']['factura'][0]['subtotal'];
            $nuevoIva = $data['reparacion']['factura'][0]['iva'];

            $modificacionFactura = $facturaController->modificarFactura($id_reparacion, $nuevoMonto, $nuevoSubtotal, $nuevoIva, "reparacion_id");

            $resultadoFactura = $modificacionFactura->getData();


            if ($resultadoFactura->status === 200) {

                $modificacionDetalle = $this->modificarDetalleReparacion($detalles, $id_reparacion);

                $resultadoDetalle = $modificacionDetalle->getData();

                if ($resultadoDetalle->status === 200) {

                    $nuevoMontoTotal = $resultadoDetalle->nuevoMonto;
                    $reparacion->update(['monto' => $nuevoMontoTotal]);

                    return response()->json([
                        "mensaje" => "ReparacionActualizada actualizada",
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

    /**Modificacion de la orden detalle*/
    public function modificarDetalleReparacion($detalles, $id_reparacion)
    {
        try {
            $modificacionCorrecta = [];
            $nuevoMonto = 0;

            $detalleReparacion = DetalleReparacionPrendas::where('id_reparacion', $id_reparacion)->get();

            // Crear un arreglo para realizar un seguimiento de los detalles existentes que no se actualizarán
            $detallesNoActualizados = $detalleReparacion->toArray();

            foreach ($detalles as $item) {
                $detalleExistente = $detalleReparacion->where('id_producto', $item['id_producto'])
                    ->where('descripcion', $item['descripcion'])
                    ->first();

                if ($detalleExistente) {
                    $resultado = $detalleExistente->update([
                        'precio_unitario' => $item['precio_unitario'],
                        'cantidad' => $item['cantidad'],
                        'subtotal' => $item['subtotal']
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
                    $detalleNuevo = new DetalleReparacionPrendas([
                        'id_reparacion' => $id_reparacion,
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


            foreach ($detallesNoActualizados  as $detallesNoActualizado) {
                $detalleExistente = $detalleReparacion->where('id_producto', $detallesNoActualizado['id_producto'])
                    ->where('descripcion', $detallesNoActualizado['descripcion'])
                    ->first();

                if ($detalleExistente) {
                    $detalleExistente->delete();
                }
            }

            $reparacion = ReparacionPrendas::find($id_reparacion);
            $reparacion->update(['precio' => $nuevoMonto]);

            return response()->json([
                "mensaje" => "Reparacion actualizada",
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

    /**Metodo para actualizar el estado de la reparación */
    public function actualizarEstadoReparacion(Request $request, $id_reparacion)
    {
        //Estados
        $estados = ["Pendiente", "En Proceso", "Listo", "Entregado"];

        $nuevoEstado = $request->estado;
        $reparacion = ReparacionPrendas::find($id_reparacion);

        if ($reparacion) {
            if (in_array($nuevoEstado, $estados)) {

                $estadoActual = $reparacion->estado; //Estado actual de la orden
                $posicionEstadoActual = array_search($estadoActual, $estados); //Posicion del estado actual en $estados
                $posicionEstadoNuevo = array_search($nuevoEstado, $estados); //Posicion del nuevo estado

                if ($posicionEstadoNuevo > $posicionEstadoActual) {
                    ReparacionPrendas::where('id', $id_reparacion)->update(['estado' => $nuevoEstado]); //Si la posicion es mayor al estado actual, se actualiza el estado.

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
            return response()->json([
                "mensaje" => "Error, no se ha podido encontrar la orden",
                "status" => 404
            ], 404);
        }
    }

    /**Metodo para anular la reparacion y facturacion de la misma. */
    public function anularReparacion($id_reparacion)
    {
        try {
            $reparacion = ReparacionPrendas::find($id_reparacion);

            if ($reparacion) {
                $reparacion->estado = 'Anulada';
                $consecutivo = $reparacion->id_factura;

                $resultadoReparacion = $reparacion->update();

                if ($resultadoReparacion) {
                    $facturaController = app(FacturasController::class);
                    $resultadoFactura = $facturaController->anularFactura($consecutivo);

                    $abonoController = app(AbonosController::class);
                    $resultadoAbono = $abonoController->anularAbonoPorIdFactura($consecutivo);

                    $contentFactura = $resultadoFactura->getData();
                    $contentAbono = $resultadoAbono->getData();

                    if ($contentFactura->status === 200 && $contentAbono->status === 200 || $contentAbono->status === 422) {
                        return response()->json([
                            'mensaje' => 'Reparacion anulada, factura y abonos correspondientes tambien han sido anulados.',
                            'status' => 200
                        ], 200);
                    }
                }

                return response()->json([
                    "mensaje" => "Reparacion anulada de manera correcta",
                    "status" => 200
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error',
                'error' => $e->getMessage(),
                'status' => 422
            ]);
        }
    }

    /**Cantidad de reparaciones, por estado*/
    public function cantidadReparacion()
    {
        $reparaciones = ReparacionPrendas::all();

        $cantidad_pendientes = 0; //Reparaciones pendientes
        $cantidad_proceso = 0; //Reparaciones en proceso
        $cantidad_listos = 0; //Reparaciones listas
        $cantidad_entregados = 0; //Reparaciones entregadas
        $cantidad_anulados = 0; //Reparaciones anuladas

        foreach ($reparaciones as $reparacion) {
            if ($reparacion->estado === "Pendiente") {
                $cantidad_pendientes++;
            } elseif ($reparacion->estado === "En Proceso") {
                $cantidad_proceso++;
            } elseif ($reparacion->estado === "Listo") {
                $cantidad_listos++;
            } elseif ($reparacion->estado === "Entregado") {
                $cantidad_entregados++;
            } elseif ($reparacion->estado === "Anulada") {
                $cantidad_anulados++;
            }
        }

        $cantidad_reparaciones = $cantidad_pendientes + $cantidad_proceso + $cantidad_listos + $cantidad_entregados;

        return response()->json([
            "cantidad_reparaciones" => $cantidad_reparaciones,
            "cantidad_pendientes" => $cantidad_pendientes,
            "cantidad_enproceso" => $cantidad_proceso,
            "cantidad_listos" => $cantidad_listos,
            "cantidad_entragados" => $cantidad_entregados,
            "cantidad_anulados" => $cantidad_anulados,
            "status" => 200
        ], 200);
    }

    public function generarProforma()
    {
    }

    public function enviarCorreo()
    {
    }

    /**Metodo para validar los datos de la reparacion */
    public function validarDatosReparacion($request)
    {
        $reglas = [
            "id_empresa" => ['required', 'integer', 'exists:empresas,id'],
            "titulo" => ['required', 'string', "max:100"],
            "fecha" => ['required', 'date'],
            'precio' => ['required', 'numeric', 'regex:/^\d+(\.\d{1,2})?$/'],
            'estado' => ['required', 'string'],
        ];

        $mensajes = [
            'id_empresa.required' => 'El campo ID de empresa es obligatorio.',
            'id_empresa.integer' => 'El campo ID de empresa debe ser un número entero.',
            'id_empresa.exists' => 'El ID de empresa no existe en la tabla de empresas.',

            'titulo.required' => 'El campo estado es obligatorio.',
            'titulo.string' => 'El campo estado debe ser una cadena de texto.',

            'fecha.required' => 'El campo fecha de orden es obligatorio.',
            'fecha.date' => 'El campo fecha de orden debe ser una fecha válida.',

            'precio.required' => 'El campo precio total es obligatorio.',
            'precio.numeric' => 'El campo precio total debe ser un número.',
            'precio.regex' => 'El formato del precio total es inválido. Debe ser un número decimal con hasta dos decimales.',

            'estado.required' => 'El campo estado es obligatorio.',
            'estado.string' => 'El campo estado debe ser una cadena de texto.'
        ];

        $validador = Validator::make($request, $reglas, $mensajes);

        if ($validador->fails()) {
            return $validador->errors()->all();
        }

        return true;
    }

    /**Metodo para validar los datos de entrada del detalle */
    public function validarDatosDetalle($request)
    {
        $reglas = [
            'id_reparacion' => 'required',
            'id_producto' => 'required|exists:productos,id',
            'precio_unitario' => 'required|numeric|min:0',
            'cantidad' => 'required|integer|min:1',
            'descripcion' => 'required|string|max:255',
            'subtotal' => 'required|numeric|min:0'
        ];

        $mensajes = [
            'id_reparacion.required' => 'El campo de id de pedido es requerido.',
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

        if ($validador->fails()){
            return $validador->errors()->all();
        }

        return true;
    }
}
