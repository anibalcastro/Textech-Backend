<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Empresas;
use Illuminate\Http\Request;
use App\Http\Resources\V1\EmpresasResource;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ClientesResource;
use Illuminate\Support\Facades\Validator;

class EmpresasController extends Controller
{

    /**Funcion retorna todos los datos de la tabla empresas */
    public function index()
    {
        return EmpresasResource::collection(Empresas::all());
    }

    /**Muestra las empresas */
    public function mostrar(Empresas $empresa)
    {
        return new ClientesResource($empresa);
    }

    /**Eliminar empresas */
    public function destruir(Empresas $empresa)
    {
        if ($empresa->delete()) {
            return response()->json(['mensaje' => 'Eliminado con exito', 'status' => 200]);
        } else {
            return response()->json(['mensaje' => 'No se encontró la empresa', 'status' => 404]);
        }
    }

    /**Registra empresas */
    public function registrarEmpresa(Request $request)
    {
        try {
            $validador = $this->validarData($request);

            if ($validador === true) {
                $crearEmpresa = Empresas::create($request->all());
                $resultado = $crearEmpresa->save();

                if ($resultado) {
                    return response()->json([
                        'data' => $request->all(),
                        'mensaje' => 'Empresa creada con éxito',
                        'status' => 200
                    ], 200);
                } else {
                    return response()->json([
                        'mensaje' => 'Error al guardar la empresa',
                        'status' => 500,
                        'data' => $crearEmpresa
                    ], 500);
                }
            } else {
                return response()->json([
                    'mensaje' => 'Los datos ingresados son incorrectos',
                    'error' => $validador,
                    'status' => 500
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e,
                'mensaje' => 'Error al registrar la empresa'
            ], 500);
        }
    }

    /**Modifica las empresas que vienen por identificador. */
    public function modificarEmpresa(Request $request, $id)
    {
        try {
            $validador = $this->validarData($request);
            $empresa = Empresas::find($id);

            if ($validador && $empresa) {
                $empresa->update($request->all());
                return response()->json(
                    [
                        'data' => $empresa,
                        'message' => 'empresa modificado con éxito',
                        'status' => 200,
                    ],
                    200
                );
            } else {
                return response()->json(
                    [
                        'message' => 'empresa no encontrado',
                        'validador_datos' => $validador,
                        'validador_empresa' => $empresa,
                        'status' => 404
                    ],
                    404
                );
            }
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error',
                'status' => 500,
                'error' => $e,
                'data' => $request->all()

            ], 500);
        }
    }

    /**Elimina la empresa que viene por el id en la url */
    public function eliminarEmpresa($id_empresa)
    {
        try {
            $empresa = Empresas::find($id_empresa);
            if (!$empresa) {
                return response()->json([
                    'mensaje' => 'La empresa no existe',
                    'status' => 404
                ]);
            } else {
                $empresa->delete();
                return response()->json([
                    'mensaje' => 'Empresa eliminado',
                    'status' => 200
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e,
                'mensaje' => 'Error al eliminar la empresa',
                'status' => 500
            ], 500);
        }
    }

    public function cantidadEmpresas()
    {
        $empresas = Empresas::all();
        $cantidad = count($empresas);

        return response()->json([
            'cant_empresas' => $cantidad,
            'status' => 200
        ]);
    }


    /**Valida los datos */
    public function validarData(Request $request)
    {
        $reglas = [
            'nombre_empresa' => 'required|string',
            'razon_social' => 'required|string',
            'cedula' => 'required|unique:empresas,cedula',
            'email' => 'required|email|unique:empresas,email',
            'nombre_encargado' => 'required|string',
            'telefono_encargado' => 'required|string',
            'direccion' => 'required|string',
            'comentarios' => 'nullable|string'
        ];


        $mensajes = [
            'required' => 'El campo :attribute es requerido.',
            'string' => 'El campo :attribute debe ser una cadena de caracteres.',
            'email' => 'El campo :attribute debe ser una dirección de correo electrónico válida.',
            'unique' => 'El valor del campo :attribute ya está en uso.'
        ];

        $validador = Validator::make($request->all(), $reglas, $mensajes);

        if ($validador->fails()) {
            return $validador->errors()->all();
        }

        return true;
    }
}
