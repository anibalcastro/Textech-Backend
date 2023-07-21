<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Mediciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\V1\MedicionesResource;


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
                'mensaje' => 'Con exito', 204
            ]);
        }
        return response()->json([
            'mensaje' => 'No se encuentra', 404
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
            // Valida los datos del request
            $this->validateData($request);

            // Verifica si el artículo ya existe en las mediciones del cliente
            $existeMedida = Mediciones::where('id_cliente', $request->id_cliente)
                ->where('articulo', $request->articulo)
                ->exists();

            if ($existeMedida) {
                return response()->json([
                    'mensaje' => "Error, ya existen medidas del cliente para el artículo " . $request->articulo,'status'=> 300,
                ], 404);
            } else {
                // Crea el registro
                $nRegistro = Mediciones::create($request->all());

                if ($nRegistro) {
                    // Retornamos una respuesta
                    return response()->json([
                        'data' => $request->all(),
                        'mensaje' => 'Registro creado con éxito',
                        'status' => 200
                    ]);
                } else {
                    return response()->json([
                        'mensaje' => 'Error, no se ha podido almacenar',
                        'status' => 404
                    ]);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e,
                'mensaje' => 'Error, hay datos nulos'
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

        //Resultado de la consulta donde id_cliente es igual al parametro.
        $resultado = Mediciones::where('id_cliente', $id_cliente)->get();

        //Valida si el resultado es vacio
        if (empty($resultado)) {

            //Retorna una respuesta
            return response()->json([
                'mensaje' => 'No se encontró ningun usuario'
            ], 404);
        }

        // Retorna la informacion del cliente con todas las medidas
        return response()->json([
            'data' => $resultado,
            'mensaje' => 'Mediciones del cliente'
        ], 200);
    }

    public function almacenarArchivo(Request $request, $id_medida)
    {

        $this->validarArchivo($request);


        if ($request->hasFile('archivo')) {
            $path = $request->file('archivo')->store('carpeta_destino');


            //Almacenar el path y la identificación de las mediciones.
            return response()->json([
                'data' => $path,
                'mensajes' => 'Se ha almacenado el archivo'
            ], 200);
        } else {
            return response()->json([
                'error' => 'No se ha enviado ningun archivo, o no tiene la extension correcta'
            ], 404);
        }
    }

    public function validarArchivo(Request $request)
    {
        $validator = $request->validate([
            'archivo' => 'required|file|mimes:pdf,png,heic,jpg',
        ]);

        if ($validator) {
            return response()->json([
                'error' => $validator,
                'message' => 'Error en los datos proporcionados.'
            ], 422);
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
            return response()->json([
                'errors' => $validator->errors(),
                'message' => 'Error en los datos proporcionados.'
            ], 422);
        }
    }
}
