<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Productos;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\V1\ProductosResource;

class ProductosController extends Controller
{
    //
    public function index()
    {
        return ProductosResource::collection(Productos::all());
    }

    /**
     * Registra nuevos productos.
     */
    public function registrarProducto(Request $request)
    {

        try {

            $validador = $this->validarDatos($request);

            if ($validador) {
                $crearProducto = Productos::create($request->all());

                $resultado = $crearProducto->save();

                if ($resultado) {
                    return response()->json([
                        'data' => $crearProducto,
                        'mensaje' => 'Producto creado con éxito',
                        'status' => 200
                    ], 200);
                } else {
                    return response()->json([
                        'data' => $crearProducto,
                        'mensaje' => 'Producto no se pudo crear',
                        'status' => 500,
                        'resultado' => $resultado
                    ], 500);
                }
            }

            return response()->json([
                'mensaje' => 'Los datos ingresados son incorrectos',
                'error' => $validador,
                'status' => 500
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e,
                'mensaje' => 'Error al registrar el producto'
            ], 500);
        }
    }

    /**
     * Modifica producto por medio del idententificador {$id}
     * edita todos los atributos que vienen en el request {$request}
     */
    public function modificarProducto(Request $request, $id)
    {
        $validador = $this->validarDatos($request);
        $producto = Productos::find($id);

        if ($validador && $producto ){
            $producto->update($request->all());

            return response()->json([
                'data' => $producto,
                'mensaje' => 'Producto modificado',
                'status' => '200'
            ],200);
        }
        else{
            return response()->json([
                'message' => 'empresa no encontrado',
                'validador_datos' => $validador,
                'validador_producto' => $producto,
                'status' => 404
            ],404);
        }
    }

    /**
     * Elimina producto por medio del identificador
     */
    public function eliminarProducto($id_producto)
    {
        $producto = Productos::find($id_producto);

        if($producto){
            $producto->delete();
            return response()->json([
                'mensaje' => 'Producto eliminado',
                'status' => 200
            ],200);
        }

        return response()->json([
            'mensaje' => 'El producto no existe',
            'status' => 404
        ]);
    }

    /**
     * Modifica el precio de un producto en especifico.
     */
    public function modifcarPrecioProducto(Request $request, $id_producto)
    {
        $producto = Productos::find($id_producto);

        $validadorPrecio = $this->validarDatoPrecio($request);

        if ($producto && $validadorPrecio === true) {
            Productos::where('id', $id_producto)->update(['precio_unitario' => $request->precio_unitario]);
            return response()->json([
                'message' => 'Precio del producto actualizado correctamente',
                'status' => 200
            ], 200);
        } else {
            return response()->json([
                'message' => 'Producto no encontrado o datos inválidos',
                'errors' => $validadorPrecio,
                'status' => 404
            ], 404);
        }
    }

    public function cantidadProductos(){
        $productos = Productos::all();
        $cantidadProductos = count($productos);

        return response()->json([
            'cantidad' => $cantidadProductos,
            'status' => 200,
        ],200);
    }

    /**
     * Valida formato de precio_unitario
     */
    public function validarDatoPrecio($request){
        $regla = [
            'precio_unitario' =>  ['required', 'numeric', 'regex:/^\d+(\.\d{1,2})?$/']
        ];


        $messages = [
            'precio_unitario.required' => 'El campo precio es obligatorio.',
            'precio_unitario.numeric' => 'El campo precio debe ser un número.',
            'precio_unitario.regex' => 'El formato del precio es inválido. Debe ser un número decimal con hasta dos decimales.'
        ];

        $validador = Validator::make($request->all(), $regla, $messages);

        if ($validador->fails()) {
            return $validador->errors()->all();
        }

        return true;
    }

    /**
     * Valida los datos de entrada, que cumplan algunas caracteristicas
     * Y retorna mensajes de posibles errores en los datos ingresados.
     */
    public function validarDatos($request)
    {
        $reglas = [
            'nombre_producto' => ['required', 'string', 'max:100', 'unique:productos'],
            'descripcion' => ['required', 'string'],
            'precio_unitario' => ['required', 'numeric', 'regex:/^\d+(\.\d{1,2})?$/'],
            'categoria' => ['required', 'string', 'max:100']
        ];

        $mensajes = [
            'nombre_producto.required' => 'El campo nombre del producto es obligatorio.',
            'nombre_producto.string' => 'El campo nombre del producto debe ser una cadena de texto.',
            'nombre_producto.max' => 'El campo nombre del producto no debe exceder los :max caracteres.',
            'nombre_producto.unique' => 'El nombre del producto ya está en uso, por favor elige otro.',

            'descripcion.required' => 'El campo descripción es obligatorio.',
            'descripcion.string' => 'El campo descripción debe ser una cadena de texto.',

            'precio_unitario.required' => 'El campo precio unitario es obligatorio.',
            'precio_unitario.numeric' => 'El campo precio unitario debe ser un número.',
            'precio_unitario.regex' => 'El formato del precio es inválido. Debe ser un número decimal con hasta dos decimales.',

            'categoria.required' => 'El campo categoría es obligatorio.',
            'categoria.string' => 'El campo categoría debe ser una cadena de texto.',
            'categoria.max' => 'El campo categoría no debe exceder los :max caracteres.'
        ];

        $validador = Validator::make($request->all(), $reglas, $mensajes);

        if ($validador->fails()) {
            return $validador->errors()->all();
        }

        return true;
    }
}
