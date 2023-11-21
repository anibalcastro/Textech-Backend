<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Categorias;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\V1\CategoriasResource;

class CategoriasController extends Controller
{
    /**
     * Muestra todos los clientes, de manera paginada.
     */
    public function index()
    {
        return CategoriasResource::collection(Categorias::all());
    }


    /**
     * Funcion creada para insertar una categoria a la base de datos.
     * Primeramente pasa por una validación de los datos.
     */
    public function registrarCategoria(Request $request)
    {
        $validarDatos = $this->validateData($request);

        if ($validarDatos !== true) {
            return response()->json([
                'mensaje' => 'Error, la categoria ingresada es incorrecta',
                'error' => $validarDatos,
                'status' => 422
            ], 422);
        }


        $nuevaCategoria = Categorias::create($request->all());

        if ($nuevaCategoria) {
            return response()->json([
                'mensaje' => 'La categoria se agregó correctamente.',
                'status' => 200
            ], 200);
        } else {
            return response()->json([
                'mensaje' => 'La categoria no se pudo agregar a la base de datos',
                'error' => $nuevaCategoria,
                'status' => 422
            ], 422);
        }
    }

    /**Metodo para eliminar una categoria */
    public function eliminarCategoria($id_categoria)
    {
        $categoria = Categorias::find($id_categoria);

        if ($categoria) {
            $resultado = $categoria->delete();

            if ($resultado) {
                return response()->json([
                    'mensaje' => 'La categoria se eliminó de manera correcta',
                    'status' => 200,
                ], 200);
            } else {
                return response()->json([
                    'mensaje' => 'La categoria no se pudo eliminar',
                    'status' => 422,
                    'error' => $resultado
                ]);
            }
        } else {
            return response()->json([
                'mensaje' => 'La categoria no se ha encontrado en la base de datos',
                'status' => 422,
            ], 422);
        }
    }


    /**
     * Función creada para validar los tipos de datos que entran en el request
     * En dado caso que alguna validacion falle, se mostrará un mensaje con el motivo del fallo
     */
    public function validateData($request)
    {
        // Extract data from the request
        $data = $request->all();

        // Reglas
        $rules = [
            'nombre_categoria' => 'required|unique:categorias|max:60',
        ];

        // Mensajes
        $messages = [
            'nombre_categoria.required' => 'El campo nombre es obligatorio.',
            'nombre_categoria.string' => 'El campo nombre debe ser una cadena de caracteres.',
            'nombre_categoria.max' => 'El campo nombre no debe exceder los 60 caracteres.',
        ];

        // Validacion
        $validator = Validator::make($data, $rules, $messages);

        // Si la validación falla, muestra el error.
        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        return true;
    }
}
