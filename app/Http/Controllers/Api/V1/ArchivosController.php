<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Archivos;

class ArchivosController extends Controller
{
    //

    public function almacenarArchivo(Request $request, $id_medida){

        $this->validarArchivo($request);


        if ($request->hasFile('archivo')) {
            $path = $request->file('archivo')->store('carpeta_destino');

            $data = [
                'id_mediciones' => $request->id_medida,
                'path' => $path
            ];


            $archivos = Archivos::create($data);

            $archivos->save();

            //Almacenar el path y la identificación de las mediciones.
            return response()->json([
                'data' => $archivos,
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
            'id_mediciones' => 'required'
        ]);

        if($validator){
            return response()->json([
                'error' => $validator,
                'message' => 'Error en los datos proporcionados.'
            ], 422);
        }
    }
}
