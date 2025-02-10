<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Mediciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\V1\MedicionesResource;
use App\Models\Clientes;
use Carbon\Carbon;


class MedicionesController extends Controller
{
    /**
     * Funcion que retorna todas los datos de la base de datos
     */
    public function index()
    {
        return MedicionesResource::collection(Mediciones::all());
    }


    public function show()
    {
        $mediciones = DB::table('clientes as c')
            ->join('mediciones as m', 'm.id_cliente', '=', 'c.id')
            ->select('c.nombre', 'c.apellido1', 'c.apellido2', 'c.cedula', 'm.*')
            ->get();

        return response()->json([
            'data' => $mediciones,
            'status' => 200
        ]);
    }

    /**
     * Elimina mediciones
     */
    public function destroy(Mediciones $mediciones)
    {
        if ($mediciones->delete()) {
            return response()->json([
                'mensaje' => 'Con exito',
                204
            ]);
        }
        return response()->json([
            'mensaje' => 'No se encuentra',
            404
        ]);
    }

    public function eliminarMedida($id_medicion)
    {
        try {
            // Validar los datos del request si es necesario

            // Verificar si la medida existe en la base de datos
            $medida = Mediciones::find($id_medicion);
            if (!$medida) {
                return response()->json([
                    'mensaje' => 'La medida no existe',
                    'status' => 404
                ]);
            }

            // Eliminar la medida
            $medida->delete();

            return response()->json([
                'mensaje' => 'Medida eliminada correctamente',
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e,
                'mensaje' => 'Error al eliminar la medida'
            ], 500);
        }
    }


    /**
     * Registra medidas en la base de datos, de un cliente especifico.
     */
    public function registrarMedida(Request $request)
    {
        try {
            // Iniciar la transacción
            DB::beginTransaction();

            // Valida los datos del request
            $validar = $this->validateData($request);

            //Nombre del cliente
            $cliente = Clientes::where('id', $request->id_cliente)->first();
            $nombreCliente = $cliente ? $cliente->nombre . ' ' . $cliente->apellido1 . ' ' . $cliente->apellido2 : null;

            // Verifica si el artículo ya existe en las mediciones del cliente
            $existeMedida = Mediciones::where('id_cliente', $request->id_cliente)
                ->where('articulo', $request->articulo)
                ->exists();

            if ($existeMedida) {

                $this->guardarRequestEnArchivo($request, $nombreCliente, 'Medida existente en base de datos');
                return response()->json([
                    'error' => "Error, ya existen medidas del cliente para el artículo " . $request->articulo,
                    'status' => 422,
                ], 422);
            } else {
                if ($validar === true) {
                    // Crea el registro dentro de la transacción
                    $nRegistro = Mediciones::create($request->all());
                    if ($nRegistro) {
                        // Commit de la transacción si todo va bien
                        DB::commit();
                        // Retornamos una respuesta
                        return response()->json([
                            'data' => $request->all(),
                            'mensaje' => 'Registro creado con éxito',
                            'status' => 200
                        ]);
                    } else {
                        // Rollback si falla la creación del registro
                        DB::rollBack();

                        $this->guardarRequestEnArchivo($request, $nombreCliente, $nRegistro);

                        return response()->json([
                            'mensaje' => 'Error, no se ha podido almacenar',
                            'status' => 404
                        ]);
                    }
                } else {
                    return response()->json([
                        'error' => $validar,
                        'status' => 422
                    ], 422);
                }
            }
        } catch (\Exception $e) {
            // Rollback en caso de excepción
            DB::rollBack();

            $this->guardarRequestEnArchivo($request, $nombreCliente, $e);

            return response()->json([
                'error' => $e,
                'mensaje' => 'Error, hay datos nulos'
            ]);
        }
    }

    public function addMeasurement(Request $request){
        try {

            //Validacion de los datos si estón bien.
            $validateDataMeasurement = $this->validateData($request);

            if($validateDataMeasurement){

                //Query para buscar si la medicion del cliente existe.
                $existsMeasurement = Mediciones::where('id_cliente', $request->id_cliente)->where('articulo', $request->articulo)->exists();

                //Valida si la medicion existe retorna la respuesta que ya no se puede almacenar porque ya se registró.
                if($existsMeasurement){
                    return response()->json([
                        'message' => 'Error, el articulo ya esta en la base de datos, no se puede agregar la medición',
                        'success' => false,
                        'status' => 400
                    ]);
                }
                else{
                    //Almacena las mediciones en la base de datos
                   Mediciones::create($request->all());

                    //valida que la medición se guardó
                    $existsNewMeasurement = Mediciones::where('id_cliente', $request->id_cliente)->where('articulo', $request->articulo)->exists();

                    //Valida si la medicion ya existe en la bd retorna la respuesta.
                    if($existsNewMeasurement){
                        return response()->json([
                          'message' => 'La medición se guardó con éxito',
                          'success' => true,
                          'status' => 200
                        ]);
                    }else{
                        return response()->json([
                            'message' => 'Las mediciones no se han almacenado',
                            'success' => false,
                            'status' => 400
                        ]);
                    }

                }

            }
            else{
                return response()->json([
                    'message' => $validateDataMeasurement,
                    'success' => false,
                    'status' => 400
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e,
                'success' => false,
                'status' => 400
            ]);
        }
    }


    /**
     * Modifica una medida especifica, por medio del identificador de la medida.
     */
    public function modificarMedida(Request $request, $id)
    {
        try {
            //Valida la medida
            $this->validateData($request);

            //Se almacena el resultado, se busca en la base de datos la informacion que tenga el identificador facilitado.
            $medida = Mediciones::find($id);

            //Valida si existe la medida
            if ($medida) {

                // Modifica la medida exceptuando el id_cliente y fecha.
                $medida->update($request->except('id_cliente', 'fecha'));

                // Retorna una respuesta
                return response()->json([
                    'data' => $medida,
                    'mensaje' => 'Medición modificada con éxito',
                    'status' => 200
                ], 200);
            } else {

                //Medicion no encontrada.
                return response()->json([
                    'mensaje' => 'Medición no encontrada',
                    'status' => 404,
                ], 404);
            }
        } catch (\Exception $e) {
            //throw $th;
            // Obtener el mensaje de error
            $error_message = $e->getMessage();

            // Obtener los datos que se enviaron en la solicitud
            $request_data = $request->all();

            return response()->json([
                'error' => $error_message,
                'request_data' => $request_data,
                'mensaje' => 'Error, hay datos nulos'
            ], 500);
        }
    }

    /**
     *
     * Retorna informacion de todas las mediciones de un cliente especifico
     * {$id_cliente} => Identificador primario del cliente.
     *
     */
    public function retornarMedicionesCliente($id_cliente)
    {
        if (!is_numeric($id_cliente) || $id_cliente <= 0) {
            return response()->json([
                'mensaje' => 'ID de cliente inválido'
            ], 400);
        }
        try {
            //Resultado de la consulta donde id_cliente es igual al parametro.
            $resultado = Mediciones::where('id_cliente', $id_cliente)->get(['id', 'articulo', 'fecha']);

            // Valida si el resultado está vacío
            if ($resultado->isEmpty()) {
                return response()->json([
                    'mensaje' => 'No hay mediciones registradas del usuario'
                ], 404);
            }

            // Retorna la informacion del cliente con todas las medidas
            return response()->json([
                'data' => $resultado,
                'mensaje' => 'Mediciones del cliente'
            ], 200);
        } catch (\Exception $e) {
            // Manejo de excepciones generales
            return response()->json([
                'mensaje' => 'Error en la consulta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**Función para obtener cantidad de mediciones */
    public function cantidadMediciones()
    {
        $cantidad = Mediciones::count();
        return response()->json([
            'cantidad_mediciones' => $cantidad,
            'status' => 200
        ], 200);
    }


    /**Retorna medicion por Id */
    public function medidaId($id)
    {

        if (!is_numeric($id) || $id <= 0) {
            return response()->json([
                'mensaje' => 'ID de cliente inválido'
            ], 400);
        }

        try {
            $resultado = Mediciones::where('id', $id)->get();

            // Valida si el resultado está vacío
            if ($resultado->isEmpty()) {
                return response()->json([
                    'mensaje' => 'No se encontró ninguna medicion'
                ], 404);
            }

            // Retorna la información del cliente con todas las medidas
            return response()->json([
                'data' => $resultado,
                'mensaje' => "Mediciones con identificador $id"
            ], 200);
        } catch (\Exception $e) {
            // Manejo de excepciones generales
            return response()->json([
                'mensaje' => 'Error en la consulta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Funcion para validar los datos que vienen en el request.
     */
    public function validateData(Request $request)
    {
        //Reglas
        $rules = [
            'id_cliente' => 'required|integer',
            'articulo' => 'required|string|max:70',
            'largo_inferior' => 'nullable',
            'cintura_inferior' => 'nullable',
            'cadera_inferior' => 'nullable',
            'pierna_inferior' => 'nullable',
            'rodilla_inferior' => 'nullable',
            'ruedo_inferior' => 'nullable',
            'tiro_inferior' => 'nullable',
            'espalda_superior' => 'nullable',
            'talle_espalda_superior' => 'nullable',
            'talle_frente_superior' => 'nullable',
            'busto_superior' => 'nullable',
            'cintura_superior' => 'nullable',
            'cadera_superior' => 'nullable',
            'largo_manga_superior' => 'nullable',
            'ancho_manga_superior' => 'nullable',
            'largo_total_superior' => 'nullable',
            'alto_pinza_superior' => 'nullable',
            'fecha' => 'required',
            'observaciones' => 'nullable|string',
            'ancho_espalda_superior' => 'nullable',
            'largo_total_espalda_superior' => 'nullable',
            'separacion_busto_superior' => 'nullable',
            'hombros_superior' => 'nullable',
            'puno_superior' => 'nullable',
            'altura_cadera_inferior' => 'nullable',
            'altura_rodilla_inferior' => 'nullable',
            'contorno_tiro_inferior' => 'nullable',
        ];

        //Mensajes
        $messages = [
            'id_cliente.required' => 'El campo ID del cliente es obligatorio.',
            'id_cliente.integer' => 'El campo ID del cliente debe ser un número entero.',
            'articulo.required' => 'El campo artículo es obligatorio.',
            'articulo.string' => 'El campo artículo debe ser una cadena de texto.',
            'articulo.max' => 'El campo artículo no debe exceder los 70 caracteres.',
            'fecha.required' => 'El campo fecha es obligatorio.',
        ];

        //Funcion que valida las reglas
        $validator = Validator::make($request->all(), $rules, $messages);


        //Si la validación falla, muestra el error.
        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        return true;
    }


    public function guardarRequestEnArchivo($request, $cliente, $error)
    {
        // Obtener la fecha actual en el formato especificado
        $fecha = Carbon::now()->format('d/m/Y H:i:s');

        // Crear la estructura del mensaje a guardar en el archivo
        $mensaje = "fecha: " . $fecha . " | error: " . $error . " |  cliente: " . $cliente . " | datos: " . json_encode($request->all(), JSON_PRETTY_PRINT);


        $filename = "error_logs_mediciones.txt";

        if (!Storage::exists($filename)) {
            Storage::put($filename, ''); // Crear el archivo si no existe
        }

        // Guardar el mensaje en un archivo txt en el servidor
        Storage::append($filename, $mensaje);
    }
}
