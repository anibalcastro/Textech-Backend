<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Facturas;
use App\Models\OrdenPedido;
use App\Models\Empresas;
use App\Models\ReparacionPrendas;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\V1\FacturasResource;

class FacturasController extends Controller
{
    /**
     * Funcion retorna todos las datos de facturación desde el mas reciente al mas antiguo
     */
    public function index()
    {
        return FacturasResource::collection(Facturas::lasted()->first());
    }

    public function modificarFactura($id, $monto, $subtotal, $iva, $columna)
    {
        if ($columna == "orden_id") {
            $factura = Facturas::where('orden_id', $id)->first();
        } else {
            $factura = Facturas::where('reparacion_id', $id)->first();
        }


        if (!$factura) {
            return response()->json([
                'mensaje' => 'No se encontró la factura para la orden proporcionada',
                'status' => 404
            ], 404);
        }

        $saldo_restante_actual = $factura->saldo_restante;
        $monto_anterior = $factura->monto;

        $monto_abonado = $monto_anterior - $saldo_restante_actual;

        if ($monto_abonado > $monto) {
            return response()->json([
                'mensaje' => 'El monto abonado es mayor que el monto actual de la factura',
                'status' => 422
            ], 422);
        }

        $factura->monto = $monto;
        $factura->subtotal = $subtotal;
        $factura->iva = $iva;
        $factura->saldo_restante = $monto - $monto_abonado;

        $resultado = $factura->update();

        if ($resultado) {
            return response()->json([
                'mensaje' => 'La factura ha sido actualizada con éxito',
                'status' => 200
            ], 200);
        } else {
            return response()->json([
                'mensaje' => 'La factura no se ha sido actualizado',
                'error' => $resultado,
                'status' => 422
            ], 422);
        }
    }

    /**
     * Funcion genera una factura mediante una orden de pedido
     */
    public function generarFactura(Facturas $factura)
    {
        try {
            //Se inicia el objeto
            $nuevaFactura = new Facturas();

            //Se llena el objeto
            $nuevaFactura->orden_id = $factura->order_id;
            $nuevaFactura->reparacion_id = $factura->reparacion_id;
            $nuevaFactura->empresa_id = $factura->empresa_id;
            $nuevaFactura->subtotal = $factura->subtotal;
            $nuevaFactura->iva = $factura->iva;
            $nuevaFactura->monto = $factura->monto;
            $nuevaFactura->fecha = $factura->fecha;
            $nuevaFactura->metodo_pago = $factura->metodo_pago;
            $nuevaFactura->saldo_restante = $factura->saldo_restante;
            $nuevaFactura->comentarios = $factura->comentario;
            $nuevaFactura->estado = $factura->estado;
            $nuevaFactura->cajero = $factura->cajero;

            //Se valida que los datos ingresados sean optimos para la base de datos.
            $resultadoValidacion = $this->validarDatosFacturaObj($nuevaFactura);

            //Si es correcto, la factura se almacena en la base de datos
            if ($resultadoValidacion === true) {
                $nuevaFactura->save();

                //Se retorna una respuesta json
                return response()->json([
                    'data' => $nuevaFactura,
                    'mensaje' => 'Factura generada con éxito',
                    'status' => 200
                ]);
            } else {
                //Retorna el mensaje de error, del porque no s epuede almacenar.
                return response()->json([
                    'error' => $resultadoValidacion,
                    'mensaje' => 'Los datos ingresados no son correctos',
                    'status' => 500
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'data' => $nuevaFactura,
                'mensaje' => 'Error al generar la factura: ' . $e->getMessage(),
                'status' => 500,
            ]);
        }
    }

    /**
     * Consulta el monto restante de una factura consecutiva.
     */
    public function consultarMontoRestante($consecutivo)
    {
        // Se encuentra la factura del consecutivo
        $factura = Facturas::findOrFail($consecutivo);

        if ($factura) {
            $montoRestante = $factura->saldo_restante;

            return response()->json([
                'data' => $montoRestante,
                'mensaje' => 'Se ha encontrado con éxito el monto restante',
                'status' => 200
            ]);
        }

        return response()->json([
            'mensaje' => 'No se ha encotrado la factura con ese número de consecutivo',
            'status' => 404
        ]);
    }

    /**
     * Actualiza el monto restante de una factura.
     */
    public function actualizarMontoRestante($consecutivo, $montoAbonar, $accion)
    {
        $validator = Validator::make(['abono' => $montoAbonar], [
            'abono' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'bool' => false,
                'mensaje' => 'El monto no es tipo numeric.',
                'status' => 500
            ]);
        } else {

            $factura = Facturas::find($consecutivo);

            if ($factura) {

                $montoPendiente = $factura->saldo_restante;

                if ($accion === 'suma') {
                    $nuevoMontoPendiente = $montoPendiente + $montoAbonar;

                    $factura->saldo_restante = $nuevoMontoPendiente;
                    $factura->update();

                    return response()->json([
                        'bool' => true,
                        'data' => $factura,
                        'mensaje' => 'Se actualizó el monto restante',
                        'status' => 200
                    ]);
                } else {

                    if ($montoAbonar > $montoPendiente) {
                        return response()->json([
                            'bool' => false,
                            'mensaje' => 'Error, el monto a abonar, no puede ser mayor al saldo pendiente',
                            'status' => 500
                        ]);
                    } else {
                        $nuevoMontoPendiente = $montoPendiente - $montoAbonar;



                        $factura->saldo_restante = $nuevoMontoPendiente;
                        $factura->update();

                        return response()->json([
                            'bool' => true,
                            'data' => $factura,
                            'mensaje' => 'Se actualizó el monto restante',
                            'status' => 200
                        ]);
                    }
                }
            } else {
                return response()->json([
                    'bool' => false,
                    'mensaje' => 'Factura no encontrada',
                    'status' => 404
                ]);
            }
        }
    }

    /**
     * Anula una factura
     */
    public function anularFactura($consecutivo)
    {
        // Obtén la factura por su ID
        $factura = Facturas::findOrFail($consecutivo);

        if ($factura) {
            // Actualiza el estado de la factura
            $factura->estado = "Nula";

            // Guarda los cambios en la base de datos
            $factura->update();

            return response()->json([
                'data' => $factura,
                'mensaje' => 'Factura anulada',
                'status' => 200
            ]);
        }

        return response()->json([
            'mensaje' => 'Factura no encontrada',
            'status' => 404
        ]);
    }


    /**
     * Consulta una factura por medio del consecutivo.
     */
    public function consultarFactura($consecutivo)
    {
        //Se obtiene facturas por medio del consecutivo
        $factura = Facturas::where('id', $consecutivo)->get();

        //Si la variable factura tiene contenido se retorna la información
        if ($factura) {
            return response()->json([
                'data' => $factura,
                'mensaje' => 'La factura se encontró con éxito.',
                'status' => 200
            ]);
        }

        //Sino retorna un mensaje de error.
        return response()->json([
            'mensaje' => 'La factura no existe.',
            'status' => 404
        ]);
    }

    /**
     * Filtra facturas por medio de una empresa especifica.
     */
    public function filtrarFacturaEmpresa($empresa_id)
    {
        //Se obtiene los datos por medio del id de la empresa
        $facturasFiltradas = Facturas::where('empresa_id', $empresa_id)->get();

        //Si tiene datos se retorna las facturas filtradas
        if ($facturasFiltradas) {
            return response()->json([
                'data' => $facturasFiltradas,
                'mensaje' => "Facturas de la empresa...",
                'status' => 200
            ]);
        }

        //Si no retorna un mensaje de error
        return response()->json([
            'mensaje' => "No existen facturas de esa empresa...",
            'status' => 404
        ]);
    }

    public function consultarFacturasOrden()
    {
        $facturas = Facturas::select('facturas.id', 'facturas.orden_id', 'orden_pedido.titulo', 'empresas.nombre_empresa', 'orden_pedido.estado', 'orden_pedido.fecha_orden', 'facturas.monto')
            ->join('orden_pedido', 'orden_pedido.id', '=', 'facturas.orden_id')
            ->join('empresas', 'empresas.id', '=', 'facturas.empresa_id')
            ->get();

        if ($facturas) {
            return response()->json([
                'data' => $facturas,
                'mensajes' => 'Proceso ejecutado correctamente',
                'status' => 200
            ], 200);
        } else {
            return response()->json([
                'data' => [],
                'mensaje' => 'No se han encontrado facturas para las ordenes',
                'status' => 422
            ], 422);
        }
    }

    public function consultarFacturasReparacion()
    {
        $facturasReparaciones = Facturas::select('facturas.id', 'facturas.reparacion_id', 'reparacion_prendas.titulo', 'empresas.nombre_empresa', 'reparacion_prendas.estado', 'reparacion_prendas.fecha', 'facturas.monto')
            ->join('reparacion_prendas', 'reparacion_prendas.id', '=', 'facturas.reparacion_id')
            ->join('empresas', 'empresas.id', '=', 'facturas.empresa_id')
            ->get();

        if ($facturasReparaciones) {
            return response()->json([
                'data' => $facturasReparaciones,
                'mensajes' => 'Proceso ejecutado correctamente',
                'status' => 200
            ], 200);
        } else {
            return response()->json([
                'data' => [],
                'mensaje' => 'No se han encontrado facturas para las reparaciones',
                'status' => 422
            ], 422);
        }
    }

    public function consultarFacturas()
    {
        $facturasReparaciones = Facturas::select('facturas.id', 'facturas.reparacion_id', 'reparacion_prendas.titulo', 'empresas.nombre_empresa', 'reparacion_prendas.estado', 'reparacion_prendas.fecha', 'facturas.monto')
            ->join('reparacion_prendas', 'reparacion_prendas.id', '=', 'facturas.reparacion_id')
            ->join('empresas', 'empresas.id', '=', 'facturas.empresa_id')
            ->get();

        $facturasOrdenes = Facturas::select('facturas.id', 'facturas.orden_id', 'orden_pedido.titulo', 'empresas.nombre_empresa', 'orden_pedido.estado', 'orden_pedido.fecha_orden', 'facturas.monto')
        ->join('orden_pedido', 'orden_pedido.id', '=', 'facturas.orden_id')
        ->join('empresas', 'empresas.id', '=', 'facturas.empresa_id')
        ->get();

        $facturas = [];

        foreach ($facturasReparaciones as $factura) {
            $facturas[] = $factura;
        }

        foreach ($facturasOrdenes as $factura) {
            $facturas[] = $factura;
        }

        // Ordenar por el campo 'id' de manera descendente
        $facturas = collect($facturas)->sortByDesc('id')->values()->all();

        return response()->json([
            'data' => $facturas,
            'mensaje' => 'Facturas encontradas',
            'status' => 200
        ],200);


    }

    /**
     * Valida que los datos ingresados en el objeto sea integral.
     */
    public function validarDatosFacturaObj(Facturas $factura)
    {
        // Define las reglas de validación para los campos de la factura
        $reglas = [
            'orden_id' => 'integer|nullable',
            'reparacion_id' => 'integer|nullable',
            'empresa_id' => 'required|integer',
            'subtotal' => 'required|numeric',
            'iva' => 'required|numeric',
            'monto' => 'required|numeric',
            'fecha' => 'required|date',
            'metodo_pago' => 'required|string',
            'saldo_restante' => 'required|numeric',
            'comentarios' => 'nullable|string',
            'estado' => 'required|string',
            'cajero' => 'required|string',
        ];

        // Define mensajes personalizados para los errores de validación
        $mensajes = [
            'required' => 'El campo :attribute es obligatorio.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'date' => 'El campo :attribute debe ser una fecha válida.',
            'string' => 'El campo :attribute debe ser una cadena de texto.',
        ];

        // Convierte el objeto de factura en un arreglo asociativo
        $datos = $factura->toArray();

        // Realiza la validación utilizando Validator
        $validador = Validator::make($datos, $reglas, $mensajes);

        // Comprueba si la validación falló
        if ($validador->fails()) {
            return $validador->errors()->all();
        }

        // Si la validación es exitosa, devuelve un true
        return true;
    }
}
