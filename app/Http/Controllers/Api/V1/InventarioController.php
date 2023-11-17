<?php

namespace App\Http\Controllers\Api\V1;

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
        return InventarioResource::collection(Inventario::latest()->get());
    }

    /**Entradas de inventario */
    public function entradas(Request $request)
    {
        try {
            $data = json_decode($request->getContent(), true);
            $mensajes = [];
            $inventario = [];

            if (count($data) > 1) {

                foreach ($data as $item) {
                    $item['cantidad'] = round($item['cantidad'], 2);
                    $nombre_producto = $item['nombre_producto'];


                    $validarExistencia = $this->validarExistencia($nombre_producto);

                    if ($validarExistencia == true) {
                        // Modificar entrada
                        $resultadoModificacion = $this->modificarEntradas($item);
                        if ($resultadoModificacion !== true) {
                            $mensajes[] = $resultadoModificacion;
                            $inventario[] = $item;
                        }
                    } else {
                        // Agregar un nuevo inventario
                        $resultadoRegistro = $this->registrarInventario($item);
                        if ($resultadoRegistro !== true) {
                            $mensajes[] = $resultadoRegistro;
                            $inventario[] = $item;
                        }
                    }
                }
            } else {
                $item = $data[0] ?? null;

                if ($item) {
                    $nombre_producto = $item['nombre_producto'];

                    $validarExistencia = $this->validarExistencia($nombre_producto);

                    if ($validarExistencia == true) {
                        // Modificar entrada
                        $resultadoModificacion = $this->modificarEntradas($item);
                        if ($resultadoModificacion !== true) {
                            $mensajes[] = $resultadoModificacion;
                            $inventario[] = $item;
                        }
                    } else {
                        // Agregar un nuevo inventario
                        $resultadoRegistro = $this->registrarInventario($item);
                        if ($resultadoRegistro !== true) {
                            $mensajes[] = $resultadoRegistro;
                            $inventario[] = $item;
                        }
                    }
                }
            }

            if (!empty($mensajes)) {
                return response()->json([
                    'mensaje' => 'Hubo errores al procesar las entradas de inventario',
                    'errores' => $mensajes,
                    'status' => 422
                ], 422);
            }

            return response()->json([
                'mensaje' => 'Se registraron las entradas exitosamente',
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

    /**Salidas de inventario */
    public function salidas(Request $request)
    {
        try {
            $data = json_decode($request->getContent(), true);
            $mensajes = [];
            $inventario = [];

            if(count($data) > 1){
                foreach ($data as $item) {
                    $resultadoSalida = $this->modificarSalida($item);
                    if ($resultadoSalida !== true) {
                        $mensajes[] = $resultadoSalida;
                        $inventario[] = $item;
                    }
                }
            }
            else{
                $resultadoSalida = $this->modificarSalida($data);

                if ($resultadoSalida !== true) {
                    $mensajes[] = $resultadoSalida;
                    $inventario[] = $data;
                }
            }


            if (count($inventario) >= 1 || count($mensajes) >= 1) {
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

        if ($inventario->isEmpty()) {
            return false;
        }

        return true;
    }

    /**Funcion para registrar inventario nuevo */
    public function registrarInventario($request)
    {
        $validarDatos = $this->validateData($request);

        if ($validarDatos !== true) {
            return $validarDatos;
        }

        // Utiliza los datos directamente desde la solicitud
        $datosInventario = $request;

        // Crea un nuevo registro de Inventario con los datos validados
        $registrarInventario = Inventario::create($datosInventario);

        // Verifica si la creación fue exitosa
        if ($registrarInventario) {
            return true;
        } else {
            return $registrarInventario; // Aquí puedes devolver más detalles sobre el error si lo deseas
        }
    }

    /**Funcion para modificar entradas de inventario, en caso que el producto ya exista */
    public function modificarEntradas($request)
    {
        $item = $request;
        $inventario = Inventario::where('nombre_producto', $item['nombre_producto'])->first();
        $validarCantidad = $this->validarCantidad(['cantidad' => $item['cantidad']]);

        if ($validarCantidad === true) {
            $cantidadAntes = $inventario->cantidad;
            $nuevaCantidad = $item['cantidad'];

            $cantidad = round($cantidadAntes + $nuevaCantidad, 2);

            // Actualizar la instancia del modelo
            $inventario->cantidad = $cantidad;
            $resultado = $inventario->save();

            if ($resultado) {
                return true;
            } else {
                return 'Error al actualizar la cantidad en el inventario.';
            }
        } else {
            return $validarCantidad;
        }
    }


    /**Funcion para modificar la salida de inventario del producto existente */
    public function modificarSalida($request)
    {
        $item = $request;
        $nombre_producto = $this->obtenerNombreProducto($item);
        $cantidad = $this->obtenerCantidadProducto($item);

        $inventario = Inventario::where('nombre_producto', $nombre_producto)->first();

        if (!$inventario) {
            return 'El producto no existe en la base de datos';
        }

        $validarCantidad = $this->validarCantidad(['cantidad' => $cantidad]);
        if ($validarCantidad === true) {
            $cantidadAntes = $inventario->cantidad;
            $nuevaCantidad = $cantidad;

            if ($nuevaCantidad<= $cantidadAntes){

                $cantidad = round($cantidadAntes - $nuevaCantidad, 2);

                $inventario->cantidad = $cantidad;
                $resultado = $inventario->save();


                if ($resultado) {
                    return true;
                } else {
                    return $resultado;
                }
            }
            else{
                return "La cantidad ingresada de salida del producto " . $nombre_producto . " es mayor a la existente.";
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
            $inventario = Inventario::where("id",$id_inventario)->first();


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

            $nombre_producto = $request['nombre_producto'];
            $color = $request['color'];
            $id_categoria = $request['id_categoria'];
            $id_proveedor = $request['id_proveedor'];
            $comentario = $request['comentario'];

            $inventario->nombre_producto = $nombre_producto;
            $inventario->color = $color;
            $inventario->id_categoria = $id_categoria;
            $inventario->id_proveedor = $id_proveedor;
            $inventario->comentario = $comentario;


            $resultado = $inventario->save();

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
        ], 200);
    }


    /**Se envia el inventario total por medio de correo */
    public function notificarCorreo()
    {
    }

    /**Funcion para validar que la cantidad ingresada sea correcta */
    public function validarCantidad($data)
    {
        // Reglas
        $rules = [
            'cantidad' => 'required|numeric|between:0,15000.99'
        ];

        // Mensajes
        $messages = [
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.numeric' => 'La cantidad debe ser un número.',
            'cantidad.between' => 'La cantidad debe estar entre :min y :max.',
        ];

        // Validacion
        $validator = Validator::make($data, $rules, $messages);

        // Si la validación falla, muestra el error.
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


    private function obtenerNombreProducto($data)
    {
        if (is_array($data) && count($data) > 0) {
            // Caso 1: Array indexado numéricamente
            if (isset($data[0]['nombre_producto'])) {
                return $data[0]['nombre_producto'];
            }

            // Caso 2: Array asociativo directo
            if (isset($data['nombre_producto'])) {
                return $data['nombre_producto'];
            }
        }

        // En caso de que la estructura no sea reconocida o no haya datos válidos
        return null; // O puedes lanzar una excepción, según tus necesidades
    }

    private function obtenerCantidadProducto($data)
    {
        if (is_array($data) && count($data) > 0) {
            // Caso 1: Array indexado numéricamente
            if (isset($data[0]['cantidad'])) {
                return $data[0]['cantidad'];
            }

            // Caso 2: Array asociativo directo
            if (isset($data['cantidad'])) {
                return $data['cantidad'];
            }
        }

        // En caso de que la estructura no sea reconocida o no haya datos válidos
        return null; // O puedes lanzar una excepción, según tus necesidades
    }
}
