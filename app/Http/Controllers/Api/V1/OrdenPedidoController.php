<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\OrdenPedido;
use Illuminate\Http\Request;
use App\Models\DetallePedido;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class OrdenPedidoController extends Controller
{
    public function index()
    {
        $ordenesConDetalles = OrdenPedido::with('detalles')->get();

        return response()->json([
            'ordenes' => $ordenesConDetalles,
            'status' => 200,
        ]);
    }

    public function crearOrden(Request $request)
    {
        try {
            // Decodifica el JSON enviado en el cuerpo de la solicitud
            $data = json_decode($request->getContent(), true);

            // Valida los datos de la orden
            $validador = $this->validarDatosOrden($data['orden']);

            if ($validador === true) {
                // Obtén la orden y los detalles del JSON
                $orden = $data['orden'];
                $detalles = $orden['detalles'];

                // Crea la orden
                $crearOrden = OrdenPedido::create($orden);

                // Guarda la orden y obtén su ID
                $idOrden = $crearOrden->id;

                // Crea los detalles de la orden
                $resultadoDetalle = $this->crearOrdenDetalle($detalles, $idOrden);

                if ($resultadoDetalle) {
                    return response()->json([
                        'mensaje' => 'Orden creada con éxito',
                        'orden' => $orden,
                        'status' => 200
                    ], 200);
                } else {
                    $ordenEliminar = OrdenPedido::find($idOrden);

                    if($ordenEliminar){
                        $ordenEliminar->delete();
                    }
                    return response()->json([
                        'mensaje' => 'Error al crear la orden de detalle',
                        'status' => 500
                    ], 500);
                }
            } else {
                return response()->json([
                    'mensaje' => 'Los datos ingresados son incorrectos',
                    'error' => $validador,
                    'status' => 500
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al registrar la orden',
                'error' => $e,
                'status' => 500
            ], 500);
        }
    }

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
        }
        else{
            return ['detalleIncorrecto' => $detallePedidoIncorrecto, 'resultado' => false];
        }
    }

    public function modificarOrden(Request $request, $id_orden)
    {

        $data = json_decode($request->getContent(), true);

        $ordenArray = $data["orden"];
        $detalles = $ordenArray["detalles"];

        $orden = OrdenPedido::find($id_orden);


        if (!$orden) {
            return response()->json([
                "mensaje" => "Error, no se ha podido encontrar la orden",
                "status" => 404
            ], 404);
        }

        $resultadoModificacion = $this->modificarOrdenDetalle($detalles); // Pasar el objeto Request

        if ($resultadoModificacion["resultado"]) {

            $nuevoMontoTotal = $resultadoModificacion["nuevoMonto"];
            $orden->update(["monto" => $nuevoMontoTotal]);

            return response()->json([
                "mensaje" => "Orden de pedido actualizada",
                "nuevoMonto" => $nuevoMontoTotal,
                "status" => 200,
            ]);
        } else {
            return response()->json([
                "mensaje" => "Error al modificar el detalle",
                "errores" => $resultadoModificacion["errores"],
                "status" => 400
            ]);
        }
    }

    public function modificarOrdenDetalle($detalles)
    {
        try {
            //code...
            $modificacionCorrecta = [];
            $nuevoMonto = 0;


            foreach ($detalles as $item) {
                $idDetalle = $item['id']; // Asegúrate de acceder al campo correctamente


                $detallePedido = DetallePedido::find($idDetalle);


                if ($detallePedido) {
                    $resultado = $detallePedido->update([
                        'id_producto' => $item['id_producto'],
                        'precio_unitario' => $item['precio_unitario'],
                        'cantidad' => $item['cantidad'],
                        'descripcion' => $item['descripcion'],
                        'subtotal' => $item['subtotal'],

                    ]);
                    $nuevoMonto += $item['subtotal'];
                    // Usa el array asociativo directamente

                    if ($resultado) {
                        $modificacionCorrecta[] = $item;
                    }
                }
            }


            if (count($modificacionCorrecta) === count($detalles)) {
                return ["resultado" => true, "nuevoMonto" => $nuevoMonto];
            } else {
                return ["resultado" => false, "errores" => $modificacionCorrecta]; // Cambia esto a los IDs actualizados
            }
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al modificar la orden',
                'error' => $e,
                'status' => 500
            ], 500);
        }
    }

    /**
     * Función para actualizar el estado del pedido.
     */
    public function actualizarEstadoPedido(Request $request, $id_orden)
    {
        //Estados
        $estados = ["Pendiente", "En Proceso", "Listo", "Entregado"];

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
                        "status" => 404
                    ], 404);
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
    public function eliminarOrden($id_orden)
    {
        $orden = OrdenPedido::find($id_orden);

        if ($orden) {
            DetallePedido::where('id_pedido', $id_orden)->delete();
            $orden->delete();

            return response()->json([
                "mensaje" => "Orden de pedido y detalle eliminada de manera correcta",
                "status" => 200
            ], 200);
        }

        return response()->json([
            "mensaje" => "Error, no se ha podido encontrar la orden",
            "status" => 404
        ], 404);
    }

    /**
     * Función que retorna cantidad de pedidos según el estado
     */
    public function cantidadOrdenEstado()
    {
        $ordenes = OrdenPedido::all();
        $cantidad_pendientes = 0;
        $cantidad_proceso = 0;
        $cantidad_listos = 0;
        $cantidad_entregados = 0;

        foreach ($ordenes as $orden) {
            if ($orden->estado === "Pendiente") {
                $cantidad_pendientes++;
            } elseif ($orden->estado === "En Proceso") {
                $cantidad_proceso++;
            } elseif ($orden->estado === "Listo") {
                $cantidad_listos++;
            } elseif ($orden->estado === "Entregado") {
                $cantidad_entregados++;
            }
        }

        return response()->json([
            "cantidad_pendientes" => $cantidad_pendientes,
            "cantidad_enproceso" => $cantidad_proceso,
            "cantidad_listos" => $cantidad_listos,
            "cantidad_entragados" => $cantidad_entregados,
            "status" => 200
        ], 200);
    }

    public function generarProforma()
    {
    }

    public function enviarProformaCorreo()
    {
    }

    /**
     * Función para validar los datos de entrada de la orden.
     */
    public function validarDatosOrden($request)
    {
        $reglas = [
            "id_empresa" => ['required', 'integer', 'exists:empresas,id'],
            "fecha_orden" => ['required', 'date'],
            'precio_total' => ['required', 'numeric', 'regex:/^\d+(\.\d{1,2})?$/'],
            'estado' => ['required', 'string']
        ];

        $mensajes = [
            'id_empresa.required' => 'El campo ID de empresa es obligatorio.',
            'id_empresa.integer' => 'El campo ID de empresa debe ser un número entero.',
            'id_empresa.exists' => 'El ID de empresa no existe en la tabla de empresas.',

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
