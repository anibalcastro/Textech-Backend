<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Abonos;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\V1\AbonosResource;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\FacturasController;


class AbonosController extends Controller
{
    public function index()
    {
        return AbonosResource::collection(Abonos::latest()->get());
    }

    public function abonosPorFactura($idFactura){
        return AbonosResource::collection(Abonos::where('factura_id', $idFactura)->get());
    }

    public function crearAbono(Request $request)
    {
        try {
            //code...
            $validarDatos = $this->validarDatos($request->all());

            if ($validarDatos === true) {


                $consecutivo = $request->factura_id;
                $montoAbonar = $request->monto;

                // Llamar a la función para actualizar el monto restante

                $facturasController = app(FacturasController::class);
                $actualizarMontoResultado = $facturasController->actualizarMontoRestante($consecutivo, $montoAbonar, 'resta');

                $resultado = $actualizarMontoResultado->getData();

                if ($resultado->status === 200) {
                    $crearAbono = Abonos::create($request->all());

                    $resultado = $crearAbono->save();

                    if ($resultado) {
                        return response()->json([
                            'data' => $request->all(),
                            'mensaje' => 'El abono ha sido registrado con éxito',
                            'status' => 200,
                        ]);
                    } else {
                        return response()->json([
                            'data' => $resultado,
                            'mensaje' => 'Error al registrar el abono',
                            'status' => 500,
                        ]);
                    }
                } else {
                    return response()->json([
                        'mensaje' => $actualizarMontoResultado['mensaje'],
                        'error' => $actualizarMontoResultado['error']
                    ]);
                }
            } else {
                return response()->json([
                    'error' => $validarDatos,
                    'mensaje' => 'Error al crear el abono',
                    'status' => 500
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'mensaje' => 'Error al registrar el abono'
            ]);
        }
    }

    public function anularAbono(Request $request)
    {
        $consecutivoAbono = $request->abono_id;

        $abono = Abonos::find($consecutivoAbono);

        if ($abono) {
            $montoAbono = $abono->monto;
            $consecutivoFactura = $abono->factura_id;
            $abono->estado = 'Anulado';

            $facturasController = app(FacturasController::class);

            $resultadoActualizacion = $facturasController->actualizarMontoRestante($consecutivoFactura, $montoAbono, 'suma');
            $resultado = $resultadoActualizacion->getData();

            if ($resultado->status === 200) {

                $resultadoModificacion = $abono->update();

                if ($resultadoModificacion) {
                    return response()->json([
                        'mensaje' => 'El abono ha sido anulado de manera correcta',
                        'status' => 200
                    ]);
                } else {
                    return response()->json([
                        'mensaje' => 'El abono no se ha podido anular de la manera correcta',
                        'status' => 500
                    ]);
                }
            } else {
                return response()->json([
                    'mensaje' => $resultado->mensaje,
                    'status' => $resultado->status
                ]);
            }
        }
    }

    public function anularAbonoPorIdFactura($idFactura)
    {
        $abonos = Abonos::where('factura_id', $idFactura)->get();

        if ($abonos->isEmpty()) {
            return response()->json([
                'mensaje' => 'No existen abonos asociados a esa factura',
                'status' => 422
            ],422);
        } else {
            // Existen abonos relacionados con esta factura, puedes proceder a actualizar su estado.
            foreach ($abonos as $abono) {
                $abono->estado = 'Anulado';
                $abono->update();
            }

            return response()->json([
                'mensaje' => 'Abonos anulados',
                'status' => 200
            ],200);
        }
    }

    public function abonorRegistradosFactura($consecutivo)
    {
        $abonosRegistrados = Abonos::where('factura_id', $consecutivo)->get();

        if ($abonosRegistrados) {
            return response()->json([
                'data' => $abonosRegistrados,
                'mensaje' => 'Abonos de la factura',
                'status' => 200
            ]);
        }

        return response()->json([
            'mensaje' => 'No se ha encontrado la factura...',
            'status' => 404
        ]);
    }

    public function validarDatos($datos)
    {
        // Define las reglas de validación para los campos
        $reglas = [
            'factura_id' => 'required|integer',
            'monto' => 'required|numeric',
            'metodo_pago' => 'required|string',
            'comentarios' => 'nullable|string',
            'estado' => 'required|string',
            'cajero' => 'required|string',
        ];

        // Define mensajes personalizados para los errores de validación
        $mensajes = [
            'required' => 'El campo :attribute es obligatorio.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'string' => 'El campo :attribute debe ser una cadena de texto.',
        ];

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
