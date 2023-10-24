<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ProductosProveedor;
use Illuminate\Support\Facades\Validator;

class ProductosProveedoresController extends Controller
{

    //Falta validar que el producto no este agregado a la base de datos...
    public function crearProductos(Request $request)
    {
        try {
            $data = json_decode($request->getContent(), true);
            $productos = $data['productos'];

            $productoValido = [];
            $productoInvalido = [];
            $contadorAgregados = 0;

            foreach ($productos as $producto) {
                //dd($producto);
                $validadorDatos = $this->validarData($producto);

                if ($validadorDatos === true) {
                    $productoValido[] = $producto;
                } else {
                    $productoInvalido[] = $producto;
                }
            }

            if (count($productoInvalido) == 0) {
                foreach ($productoValido as $producto) {
                    $proveedor_id = $producto['proveedor_id'];

                    // Validar que el producto no esté relacionado al mismo proveedor.
                    $productoExistente = ProductosProveedor::where('proveedor_id', $proveedor_id)
                        ->where('nombre_producto', $producto['nombre_producto'])
                        ->first();

                    if (!$productoExistente) {
                        // Utilizar una transacción para asegurarse de que todas las operaciones sean exitosas.
                        DB::transaction(function () use ($producto, &$contadorAgregados) {
                            $crearProducto = ProductosProveedor::create($producto);
                            if ($crearProducto->save()) {
                                $contadorAgregados++;
                            }
                        });
                    }
                }

                if ($contadorAgregados == count($productoValido)) {
                    return response()->json([
                        'mensaje' => 'Productos agregados exitosamente',
                        'data' => 200
                    ]);
                } else {
                    return response()->json([
                        'mensaje' => 'No se ha podido agregar todos los productos exitosamente',
                        'status' => 422
                    ]);
                } // Retornar false si no se agregaron correctamente todos los detalles válidos
            } else {
                return response()->json([
                    'mensaje' => 'No se ha podido agregar todos los productos asociados al proveedor',
                    'data' => $productoInvalido,
                    'status' => 422
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error en la función',
                'error' => $e->getMessage(),
                'status' => 422
            ]);
        }
    }

    public function modificarProducto(Request $request, $producto_id)
    {


        $producto = ProductosProveedor::find($producto_id);

        if (!$producto){
            return response()->json([
                'mensaje' => 'El producto no ha sido encontrado',
                'status' => 422
            ]);
        }

        $validadorDatos = $this->validarData($request->all());

        if ($validadorDatos === true){
            $producto->update($request->all());

            return response()->json([
                'mensaje' => 'El producto se ha modificado de manera correcta',
                'data' => $request->all(),
                'status' => 200
            ]);
        }
        else{
            return response()->json([
                'mensaje' => 'No se ha podido modificar el producto, porque ingresaste datos incorrecto',
                'error' => $validadorDatos,
                'status' => 422
            ]);
        }

    }

    public function eliminarProducto($producto_id)
    {
        $producto = ProductosProveedor::find($producto_id);

        if(!$producto){
            return response()->json([
                'mensaje' => 'El producto no ha sido encontrado',
                'status' => 422
            ]);
        }

        $producto->delete();

        return response()->json([
            'mensaje' => 'Producto se ha eliminado',
            'status' => 200
        ],200);
    }

    public function eliminarTodosProductos($proveedor_id)
    {
        $productos = ProductosProveedor::where('proveedor_id', $proveedor_id)->get();

       if ($productos->isEmpty()){
            return true;
       }
       else{
           DB::table('productos_proveedor')->where('proveedor_id', $proveedor_id)->delete();
       }


        return true;
    }

    public function obtenerProductosProveedor($proveedor_id){
        $productosProveedor = ProductosProveedor::where('proveedor_id', $proveedor_id)->get();

        return $productosProveedor;
    }

    public function validarData($request)
    {

        $reglas = [
            'proveedor_id' => 'required|exists:proveedores,id',
            'nombre_producto' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'precio' => 'required|numeric|between:0,9999999.99',
        ];

        $mensajes = [
            'proveedor_id.required' => 'El campo proveedor es obligatorio.',
            'proveedor_id.exists' => 'El proveedor seleccionado no es válido.',
            'nombre_producto.required' => 'El campo nombre del producto es obligatorio.',
            'nombre_producto.max' => 'El nombre del producto no debe tener más de :max caracteres.',
            'descripcion.required' => 'El campo descripción es obligatorio.',
            'precio.required' => 'El campo precio es obligatorio.',
            'precio.numeric' => 'El precio debe ser un valor numérico.',
            'precio.between' => 'El precio debe estar entre :min y :max.',
        ];

        $validador = Validator::make($request, $reglas, $mensajes);

        if($validador->fails()){
            return $validador->errors()->all();
        }

        return true;
    }
}
