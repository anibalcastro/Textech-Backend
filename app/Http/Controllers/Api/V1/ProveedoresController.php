<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Proveedores;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ProveedoresController extends Controller
{

    /**
     * Retorna informacion de proveedores con los productos asociados.
     */
    public function index()
    {

        $proveedoresConProductos = Proveedores::with('productos')->get();

        return response()->json([
            'data' => $proveedoresConProductos,
            'status' => 200
        ], 200);
    }


    /**
     * Metodo para registrar un nuevo proveedor
     */
    public function registrarProveedor(Request $request)
    {
        try {



            //Valida los datos
            $validador = $this->validarDatos($request);


            if ($validador === true) {
                //Da formato especial al numero de telefono.
                $telefono = $this->formatTelefono($request->input('telefono'));

                //Se crea el objeto de proveedor
                $objProveedor = new Proveedores();

                $objProveedor->nombre = $request->input('nombre');
                $objProveedor->direccion = $request->input('direccion');
                $objProveedor->vendedor = $request->input('vendedor');
                $objProveedor->telefono = $telefono;
                $objProveedor->email = $request->input('email');

                //Se guarda la informacion del objeto
                $resultado = $objProveedor->save();

                if ($resultado) {
                    //retorna resultado de proveedor creado exitosamente
                    return response()->json([
                        'mensaje' => 'El proveedor se ha creado exitosamente',
                        'status' => 200
                    ], 200);
                } else {
                    //retorna que el proveedor no se creo de manera correcta
                    return response()->json([
                        'mensaje' => 'El proveedor no se ha podido almacenar de manera correcta',
                        'error' => $resultado,
                        'status' => 422
                    ], 422);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'status' =>  422
            ]);
        }
    }


    /**
     * Método para darle formato al número de telefono
     */
    public function formatTelefono($telefono)
    {
        // Eliminar cualquier espacio en blanco y guiones
        $formatoTelefono = preg_replace("/[\s-]+/", "", $telefono);

        // Verificar si el número tiene exactamente 8 dígitos
        if (preg_match("/^\d{8}$/", $formatoTelefono)) {
            // Si tiene 8 dígitos, agregar el prefijo "+506"
            return "+506 " . $formatoTelefono;
        } else {
            // Si no tiene 8 dígitos, retornar el número original
            return $telefono;
        }
    }

    /**
     * Metodo que modifica informacion del proveedor por medio del identificador
     */
    public function modificarProveedor(Request $request, $proveedor_id)
    {

        //Valida los datos del request
        $validador = $this->validarDatos($request);

        //Busca el proveedor por medio del ID
        $proveedor = Proveedores::find($proveedor_id);

        if ($validador === true && $proveedor) {

            $telefonoFormateado = $this->formatTelefono($request->telefono);

            $proveedor->nombre = $request->nombre;
            $proveedor->direccion = $request->direccion;
            $proveedor->telefono = $telefonoFormateado;
            $proveedor->vendedor = $request->vendedor;
            $proveedor->email = $request->email;

            //Modifica los datos
            $proveedor->update();

            //Retorna una respuesta positiva.
            return response()->json([
                'data' => $request->all(),
                'mensaje' => 'El proveedor se ha modificado de manera correcta',
                'status' => 200,

            ], 200);
        } else {
            //Retorna un error.
            return response()->json([
                'mensaje' => 'Error al modificar el proveedor',
                'validador' => $validador,
                'proveedor' => $proveedor,
                'status' => 422
            ], 422);
        }
    }


    public function eliminarProveedor($proveedor_id)
    {
        //Eliminar el proveedor
        $proveedor = Proveedores::find($proveedor_id);
        $productosProveedorController = app(ProductosProveedoresController::class);

        if ($proveedor) {

            //Eliminar los productos de ese proveedor
            $productosProveedorController->eliminarTodosProductos($proveedor_id);
            $proveedor->delete();

            return response()->json([
                'mensaje' => 'El proveedor y los productos se han eliminado',
                'status' => 200
            ], 200);
        } else {
            return response()->json([
                'mensaje' => 'El proveedor no se ha encontrado',
                'status' => 422,
                'proveedor' => $proveedor
            ]);
        }
    }

    public function obtenerProveedor($proveedor_id)
    {
        $proveedor = Proveedores::find($proveedor_id);

        if (!$proveedor) {
            return response()->json([
                'mensaje' => 'No se ha encontrado el proveedor',
                'status' => 422
            ]);
        }

        $productosProveedorController = app(ProductosProveedoresController::class);
        $productos = $productosProveedorController->obtenerProductosProveedor($proveedor_id);

        return response()->json([
            'mensaje' => 'Proveedor y sus productos encontrados...',
            'proveedor' => $proveedor,
            'productos' => $productos,
            'status' => 200
        ]);
    }

    public function proveedoresInfo()
    {
        $resultado = DB::table('proveedores')
            ->select('id', 'nombre')
            ->get();

        return response()->json([
            'data' => $resultado,
            'status' => 200
        ], 200);
    }

    public function validarDatos($request)
    {
        $reglas = [
            'nombre' => 'required|string|max:100',
            'direccion' => 'required|string|max:100',
            'vendedor' => 'required|string|max:100',
            'telefono' => 'required|string|max:100',
            'email' => 'required|email|max:100',
        ];

        $mensajes = [
            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.max' => 'El campo nombre no puede tener más de 100 caracteres.',
            'direccion.required' => 'El campo dirección es obligatorio.',
            'direccion.max' => 'El campo dirección no puede tener más de 100 caracteres.',
            'vendedor.required' => 'El campo vendedor es obligatorio.',
            'vendedor.max' => 'El campo vendedor no puede tener más de 100 caracteres.',
            'telefono.required' => 'El campo teléfono es obligatorio.',
            'telefono.max' => 'El campo teléfono no puede tener más de 100 caracteres.',
            'email.required' => 'El campo email es obligatorio.',
            'email.email' => 'El campo email debe ser una dirección de correo electrónico válida.',
            'email.max' => 'El campo email no puede tener más de 100 caracteres.',
        ];

        $validador = Validator::make($request->all(), $reglas, $mensajes);

        if ($validador->fails()) {
            return $validador->errors()->all();
        }

        return true;
    }
}
