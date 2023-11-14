<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Models\Inventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\V1\InventarioResource;

class InventarioController extends Controller
{
    /**
     * Muestra todos los clientes, de manera paginada.
     */
    public function index()
    {
        return InventarioResource::collection(Inventario::all())->latest();
    }

    /**Entradas de inventario */
    public function entradas(Request $request)
    {
        try {
            $data = json_decode($request->getContent(), true);
            $mensajes = [];
            $inventario = [];

            foreach ($data as $item) {
                $nombre_producto = $item['nombre_producto'];

                $validarExistencia = $this->validarExistencia($nombre_producto);

                if ($validarExistencia == true) {
                    //Modificar entrada
                    $resultadoModificacion = $this->modificarEntradas($item);
                    if ($resultadoModificacion !== true) {
                        $mensaje[] = $resultadoModificacion;
                        $inventario[] = $item;
                    }
                } else {
                    //Agregar un nuevo inventario
                    $resultadoRegistro = $this->registrarInventario($item);
                    if ($resultadoRegistro !== true) {
                        $mensaje[] = $resultadoRegistro;
                        $inventario[] = $item;
                    }
                }
            }


            if (count($inventario) > 1) {
                return response()->json([
                    'mensaje' => 'Hubo inventarios que no se registraron las entradas',
                    'inventario' => $inventario,
                    'error' => $mensajes,
                    'status' => 422
                ], 422);
            }

            return response()->json([
                'mensaje' => 'Se registraron las entradas exitosamente',
                'status' => 200
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => 'Error',
                'error' => $e->getMessage(),
                'status' => 422
            ], 422);
        }
    }

    /**Salidas de inventario */
    public function salidas(Request $request)
    {
        try {
            $inventarioSalida = json_decode($request->getContent(), true);
            $mensajes = [];
            $inventario = [];

            foreach ($inventarioSalida as $item) {
                $resultadoSalida = $this->modificarSalida($item);
                if ($resultadoSalida !== true) {
                    $mensajes[] = $resultadoSalida;
                    $inventario[] = $item;
                }
            }

            if (count($inventario) > 1) {
                return response()->json([
                    'mensaje' => 'Hubo inventarios que no se registraron las salidas',
                    'inventario' => $inventario,
                    'error' => $mensajes,
                    'status' => 422
                ], 422);
            }

            return response()->json([
                'mensaje' => 'Se registraron las salidas exitosamente',
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error',
                'error' => $e->getMessage(),
                'status' => 422
            ], 422);
        }
    }

    /**Valida si el nombre del producto existe */
    public function validarExistencia($nombre_producto)
    {
        $inventario = Inventario::where('nombre_producto', $nombre_producto)->get();

        if ($inventario) {
            return true;
        }

        return false;
    }

    /**Funcion para registrar inventario nuevo */
    public function registrarInventario($request)
    {
        $validarDatos = $this->validateData($request);

        if ($validarDatos !== true) {
            return $validarDatos;
        }

        $registrarInventario = Inventario::create($request);
        $resultado = $registrarInventario->save();

        if ($resultado) {
            return true;
        } else {
            return $resultado;
        }
    }

    /**Funcion para modificar entradas de inventario, en caso que el producto ya exista */
    public function modificarEntradas($request)
    {

        $item = $request[0];
        $inventario = Inventario::where('nombre_producto', $item['nombre_producto'])->get();
        $validarCantidad = $this->validarCantidad($item['cantidad']);
        if ($validarCantidad === true) {
            $cantidadAntes = $inventario->cantidad;
            $nuevaCantidad = $item['cantidad'];

            $cantidad = round($cantidadAntes + $nuevaCantidad, 2);

            $resultado = $inventario::update(['cantidad' => $cantidad]);

            if ($resultado) {
                return true;
            } else {
                return $resultado;
            }
        } else {
            return $validarCantidad;
        }
    }

    /**Funcion para modificar la salida de inventario del producto existente */
    public function modificarSalida($request)
    {
        $item = $request[0];
        $inventario = Inventario::where('nombre_producto', $item['nombre_producto'])->get();
        if (!$inventario) {
            return 'El producto no existe en la base de datos';
        }

        $validarCantidad = $this->validarCantidad($item['cantidad']);
        if ($validarCantidad === true) {
            $cantidadAntes = $inventario->cantidad;
            $nuevaCantidad = $item['cantidad'];

            $cantidad = round($cantidadAntes - $nuevaCantidad, 2);

            $resultado = $inventario::update(['cantidad' => $cantidad]);

            if ($resultado) {
                return true;
            } else {
                return $resultado;
            }
        } else {
            return $validarCantidad;
        }
    }

    /**Funcion para modificar nombre del producto del inventario */
    public function modificarInventario(Request $request, $id_inventario)
    {
        try {
            $validador = $this->validateData($request->all());
            $inventario = Inventario::find($id_inventario);


            if (!$validador) {
                return response()->json([
                    'mensaje' => 'Error, los datos ingresados no son correctos',
                    'error' => $validador,
                    'status' => 422,
                ], 422);
            } else if (!$inventario) {
                return response()->json([
                    'mensaje' => 'Error, el producto no se logró encontrar',
                    'error' => $request->all(),
                    'status' => 422,
                ], 422);
            }

            $nombre_producto = $request[0]['nombre_producto'];
            $color = $request[0]['color'];
            $id_categoria = $request[0]['id_categoria'];
            $id_proveedor = $request[0]['id_proveedor'];
            $comentario = $request[0]['comentario'];

            $resultado = $inventario::update(
                [
                    'nombre_producto' => $nombre_producto,
                    'color' => $color,
                    'id_categoria' => $id_categoria,
                    'id_proveedor' => $id_proveedor,
                    'comentario' => $comentario
                ]
            );

            if ($resultado) {
                return response()->json([
                    'mensaje' => 'Inventario modificado',
                    'status' => 200
                ], 200);
            } else {
                return response()->json([
                    'mensaje' => 'Hubo un error al modificar el inventario',
                    'status' => 422
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'No se pudo modificar el inventario',
                'error' => $e->getMessage(),
                'status' => 422
            ], 422);
        }
    }

    /**Cantidad de inventario registrado */
    public function cantidadInventario()
    {
        $cantidad = Inventario::count();

        return response()->json([
            'mensaje' => 'Cantidad de inventario',
            'cantidad' => $cantidad,
            'status' => 200
        ], 200);
    }

    /**Retorna la informacion del inventario completa */
    public function inventario()
    {
        $resultado = DB::table('inventario as i')
            ->join('proveedores as p', 'p.id', '=', 'i.id_proveedor')
            ->join('categorias as c', 'c.id', '=', 'i.id_categoria')
            ->select('i.id', 'i.nombre_producto', 'i.cantidad', 'i.color', 'i.id_categoria', 'c.nombre_categoria', 'p.nombre as nombre_proveedor', 'i.comentario')
            ->latest('i.created_at') // Agregando el método latest
            ->get();


        return response()->json([
            'data' => $resultado,
            'status' => 200
        ],200);
    }

    /**Se genera un pdf de todo el inventario existente. */
    public function generarPdf()
    {
    }

    /**Se envia el inventario total por medio de correo */
    public function notificarCorreo()
    {
    }

    /**Funcion para validar que la cantidad ingresada sea correcta */
    public function validarCantidad($cantidad)
    {
        //Reglas
        $rules = [
            'cantidad' => 'required|numeric|between:0,15000.99'
        ];

        //Mensajes
        $messages = [
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.numeric' => 'La cantidad debe ser un número.',
            'cantidad.between' => 'La cantidad debe estar entre :min y :max.',
        ];

        //Validacion
        $validator = Validator::make($cantidad, $rules, $messages);

        //Si la validación falla, muestra el error.
        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        return true;
    }

    /**
     * Función creada para validar los tipos de datos que entran en el request
     * En dado caso que alguna validacion falle, se mostrará un mensaje con el motivo del fallo
     */
    public function validateData($request)
    {
        //Reglas
        $rules = [
            'nombre_producto' => 'required|string|max:100',
            'cantidad' => 'required|numeric|between:0,15000.99', // ajusta el rango según tus necesidades
            'color' => 'nullable|string|max:20',
            'id_categoria' => 'nullable|exists:categorias,id',
            'id_proveedor' => 'nullable|exists:proveedores,id',
            'comentario' => 'nullable|string',
        ];

        //Mensajes
        $messages = [
            'nombre_producto.required' => 'El nombre del producto es obligatorio.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.numeric' => 'La cantidad debe ser un número.',
            'cantidad.between' => 'La cantidad debe estar entre :min y :max.',
            'color.max' => 'El color no puede tener más de :max caracteres.',
            'id_categoria.exists' => 'La categoría seleccionada no es válida.',
            'id_proveedor.exists' => 'El proveedor seleccionado no es válido.',
        ];

        //Validacion
        $validator = Validator::make($request, $rules, $messages);

        //Si la validación falla, muestra el error.
        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        return true;
    }


    public function mostrarInventario()
    {
        // Obtener todos los registros del inventario
        $inventario = Inventario::all();

        // Pasar los registros a la vista
        return view('inventario', ['inventario' => $inventario]);
    }
}
