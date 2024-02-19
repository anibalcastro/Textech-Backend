<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OrdenPedidoPersona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrdenPedidoPersonaController extends Controller{

    public function index(){
        $ordenesPersonas = OrdenPedidoPersona::all();

        return response()->json(['data' => $ordenesPersonas, 'status' => 200],200);
    }

    /**Funcion para crear a una persona dentro de un pedido */
    public function crearOrdenPedidoPersona(Request $request){

        $data = json_decode($request->getContent(), true); // Obtener el JSON

        // Verificar si se recibieron datos
        if (empty($data)) {
            return response()->json([
                'mensaje' => 'No se recibieron datos válidos',
                'status' => 422
            ], 422);
        }

        $response = [];

        // Iterar sobre cada objeto en el JSON
        foreach ($data as $item) {
            // Validar cada objeto
            $validador = $this->validateData($item);

            if ($validador !== true) {
                $response[] = [
                    'mensaje' => 'No se ha podido crear, revise los datos',
                    'errors' => $validador,
                    'status' => 422
                ];
            } else {
                // Crear la OrdenPedidoPersona con los datos del objeto actual
                $ordenPedidoPersona = OrdenPedidoPersona::create([
                    'id_orden' => $item['id_orden'],
                    'prenda' => $item['prenda'],
                    'nombre' => $item['nombre'],
                    'cantidad' => $item['cantidad'],
                    'entregado' => false, // Por defecto, el campo entregado se establece en false
                ]);

                $response[] = [
                    'mensaje' => 'Orden de pedido persona creada con éxito',
                    'data' => $data,
                    'status' => 200
                ];
            }
        }

        return response()->json(['data'=> $ordenPedidoPersona, 'status' => 200],200);
    }

    /**Función creada para modificar el estado entregado */
    public function modificarEstadoEntregado($id){
        OrdenPedidoPersona::where('id', $id)->update(['entregado' => true]);

        return response()->json(['mensaje' => 'Modificado con éxito', 'status' => 200],200);
    }

    public function personasOrdenPedido($id_orden){
        $ordenPedidoPersona = OrdenPedidoPersona::where('id_orden', $id_orden)->get();

        if (!$ordenPedidoPersona){
            return response()->json([
                'data' => [],
                'status' => 422
            ],422);
        }

        return response()->json(['data'=>$ordenPedidoPersona, 'status' => 200],200);
    }

    /**Funcion para validar los datos */
    public function validateData($request){
        $reglas = [
            'id_orden' => 'required',
            'prenda' => 'required',
            'nombre' => 'required',
            'cantidad' => 'required|integer|min:1',
        ];

        $mensajes = [
            'id_orden.required' => 'El campo id_orden es requerido',
            'prenda.required' => 'El campo prenda es requerido',
            'nombre.required' => 'El campo nombre es requerido',
            'cantidad.required' => 'El campo cantidad es requerido',
            'cantidad.min' => 'El campo cantidad es de minimo 1',
            'cantidad.integer' => 'El campo cantidad tiene que ser de tipo entero',
        ];

        $validador = Validator::make($request, $reglas, $mensajes);

        if ($validador->fails()) {
            return $validador->errors()->all();
        }

        return true;
    }
}
