<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Mediciones;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\V1\MedicionesResource;
use Illuminate\Support\Facades\Storage;


class MedicionesController extends Controller
{
    /**
     * Funcion que retorna todas los datos de la base de datos
     */
    public function index()
    {
        return MedicionesResource::collection(Mediciones::latest()->paginate());
    }

    /*
    public function show(Mediciones $mediciones){
        return new Mediciones($mediciones);
    }
    */

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

    /**
     * Registra medidas en la base de datos, de un cliente especifico.
     */
    public function registrarMedida(Request $request)
    {
        try {
            //Valida los datos del request
            $this->validateData($request);

            //Obtenemos los articulos del cliente
            $articulosCliente = Mediciones::where('id_cliente', $request->id_cliente)->pluck('articulo');

            //Validamos si lo que el usuario quiere ingresar exista en el arreglo.
            if (in_array($request->articulo, $articulosCliente->toArray())){

                //Si es asi, retornamos una respuesta
                return response()->json([
                    'mensaje' => "Error, ya existen medidas al cliente del articulo " . $request->articulo,
                ], 200);
            }
            else{
                //Creamos el registro
                $nRegistro = Mediciones::create($request->all());

                //Guardamos en la base de datos.
                $nRegistro->save();

                //Retornamos una respuesta.
                return response()->json(
                    [
                        'data' => $request->all(),
                        'mensaje' => 'Registro creado con éxito'
                    ]
                );
            }
        } catch (\Exception $e) {
            //throw $th;
            return response()->json([
                'error' => $e,
                'mensaje'=>'Error, hay datos nulos'
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
                    'mensaje' => 'Medición modificada con éxito'
                ], 200);
            } else {

                //Medicion no encontrada.
                return response()->json([
                    'mensaje' => 'Medición no encontrada'
                ], 404);
            }
        } catch (\Exception $e) {
            //throw $th;
            return response()->json([
                'error' => $e,
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
    public function retornarMedicionesCliente($id_cliente){

        //Resultado de la consulta donde id_cliente es igual al parametro.
        $resultado = Mediciones::where('id_cliente', $id_cliente)->get();

        //Valida si el resultado es vacio
        if(empty($resultado)){

            //Retorna una respuesta
            return response()->json([
                'mensaje' => 'No se encontró ningun usuario'
            ],404);

        }

        // Retorna la informacion del cliente con todas las medidas
        return response()->json([
            'data'=> $resultado,
            'mensaje' => 'Mediciones del cliente'
        ],200);

    }

    public function almacenarArchivo(Request $request, $id_medida){

        $this->validarArchivo($request);


        if ($request->hasFile('archivo')) {
            $path = $request->file('archivo')->store('carpeta_destino');


            //Almacenar el path y la identificación de las mediciones.
            return response()->json([
                'data' => $path,
                'mensajes' => 'Se ha almacenado el archivo'
            ],200);


        } else {
           return response()->json([
            'error' => 'No se ha enviado ningun archivo, o no tiene la extension correcta'
           ],404);
        }
    }

    public function validarArchivo(Request $request){
        $validator = $request->validate([
            'archivo' => 'required|file|mimes:pdf,png,heic,jpg',
        ]);

        if($validator){
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
