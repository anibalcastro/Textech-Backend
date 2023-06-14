<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Mediciones;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\V1\MedicionesResource;

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
     * Crea medidas a un usuario especifico
     */
    public function registrarMedida(Request $request)
    {
        try {
            //code...
            $this->validateData($request);
            $nRegistro = Mediciones::create($request->all());

            $nRegistro->save();

            return response()->json(
                [
                    'data' => $request->all(),
                    'mensaje' => 'Registro creado con éxito'
                ]
            );
        } catch (\Exception $e) {
            //throw $th;
            return response()->json([
                'mensaje'=>'Error, hay datos nulos'
            ]);
        }
    }

    public function modifcarMedida(Request $request, $id)
    {
        try {
            //code...
            $this->validateData($request);
            $medida = Mediciones::find($id);

            if ($medida) {
                $medida->update($request->all());

                return response()->json([
                    'data' => $medida,
                    'mensaje' => 'Medición modificada con éxito'
                ], 200);
            } else {
                return response()->json([
                    'mensaje' => 'Medición no encontrada'
                ], 404);
            }
        } catch (\Exception $e) {
            //throw $th;
            return response()->json([
                'Mensaje' => 'Error, hay datos nulos'
            ], 500);
        }
    }

    public function retornarMedicionesCliente($id_cliente){
        $resultado = Mediciones::where('id_cliente', $id_cliente)->get();

        return response()->json([
            'data'=> $resultado,
            'Mensaje' => 'Mediciones del cliente'
        ],200);
    }

    /**
     * Funcion para validar los datos que vienen en el request.
     */
    public function validateData(Request $request)
    {
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

        $messages = [
            'id_cliente.required' => 'El campo ID del cliente es obligatorio.',
            'id_cliente.integer' => 'El campo ID del cliente debe ser un número entero.',
            'articulo.required' => 'El campo artículo es obligatorio.',
            'articulo.string' => 'El campo artículo debe ser una cadena de texto.',
            'articulo.max' => 'El campo artículo no debe exceder los 70 caracteres.',
            'fecha.required' => 'El campo fecha es obligatorio.',
        ];

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
