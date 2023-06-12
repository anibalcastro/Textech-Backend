<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\MedicionesResource;
use App\Models\Mediciones;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MedicionesController extends Controller
{
    public function index(){
        return MedicionesResource::collection(Mediciones::latest()->paginate());
    }

    public function show(Mediciones $mediciones){
        return new Mediciones($mediciones);
    }

    public function destroy(Mediciones $mediciones){
        if($mediciones->delete()){
            return response()->json([
                'mensaje' => 'Con exito',204
            ]);
        }
        return response()->json([
            'mensaje' => 'No se encuentra',404
        ]);
    }
}
