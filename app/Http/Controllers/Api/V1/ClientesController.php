<?php

namespace App\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\Clientes;
use App\Models\Mediciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\V1\ClientesResource;

class ClientesController extends Controller
{
    /**
     * Muestra todos los clientes, de manera paginada.
     */
    public function index()
    {
        return ClientesResource::collection(Clientes::all());
    }

    /**
     *
     */
    public function show(Clientes $clientes)
    {
        return new ClientesResource($clientes);
    }

    /**
     * Elimina un cliente por medio del identificador.
     */
    public function destroy(Clientes $clientes)
    {
        if ($clientes->delete()) {
            return response()->json([
                'mensaje' => 'Con exito', 204
            ]);
        }
        return response()->json([
            'mensaje' => 'No se encuentra', 404
        ]);
    }

    /**
     * Funcion creada para insertar un cliente a la base de datos.
     * Primeramente pasa por una validación de los datos.
     */
    public function registrarCliente(Request $request)
    {
        //Validación de los datos.
        $validador = $this->validateData($request);



        if ($validador && $request->input('nombre') && $request->input('nombre') !== 'undefined' && trim($request->input('nombre')) !== '') {

            DB::beginTransaction(); // Inicia una transacción

            try {
                // Se crea el nuevo cliente
                $cliente = Clientes::create($request->all());

                // Se almacena en la base de datos
                DB::commit(); // Confirma la transacción

                // Retorna una respuesta positiva.
                return response()->json([
                    'data' => $cliente,
                    'mensaje' => 'Cliente creado con éxito',
                    'status' => 200
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack(); // Revierte la transacción en caso de error

                // Manejo de errores
                return response()->json([
                    'mensaje' => 'Error al crear el cliente: ' . $e->getMessage(),
                    'status' => 500
                ], 500);
            }
        } else {
            // Retorna un mensaje de error si la validación falla
            return response()->json([
                'mensaje' => 'Error, el nombre no puede estar vacío o ser undefined',
                'status' => 422,
                'error' => $validador
            ], 422);
        }
    }

    public function eliminarCliente($id_cliente)
    {
        try {
            // Verificar si el cliente existe en la base de datos
            $cliente = Clientes::find($id_cliente);
            if (!$cliente) {
                return response()->json([
                    'mensaje' => 'El cliente no existe',
                    'status' => 404
                ]);
            }

            // Eliminar todas las mediciones del cliente
            Mediciones::where('id_cliente', $id_cliente)->delete();

            // Eliminar el cliente
            $cliente->delete();

            return response()->json([
                'mensaje' => 'Cliente y sus mediciones eliminados correctamente',
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e,
                'mensaje' => 'Error al eliminar el cliente y sus mediciones'
            ], 500);
        }
    }


    /**
     * Funcion creada para modificar clientes, se maneja por medio del metodo post.
     * Se recibe un id, en este caso será el id del cliente y tambien se recibe la información que desea modificar.
     */
    public function modificarCliente(Request $request, $id)
    {
        try {
            $this->validateData($request);
            $cliente = Clientes::find($id); //Cliente

            if ($cliente) {
                $cliente->update($request->all());
                return response()->json(
                    [
                        'data' => $cliente,
                        'message' => 'Cliente modificado con éxito',
                        'status' => 200,
                    ],
                    200
                );
            } else {
                return response()->json(['message' => 'Cliente no encontrado', 'status' => 404], 404);
            }
        } catch (\Exception $ex) {
            //throw $th;
            return response()->json([
                'Mensaje' => 'Error, hay datos nulos'

            ], 500);
        }
    }

    /**
     * Función que retorna la data del cliente.
     * Se busca cliente por medio de un Id
     */
    public function obtenerCliente($id)
    {
        try {
            $cliente = Clientes::find($id)->first(); // Buscar el cliente por su ID

            if ($cliente) {
                // Retornar los datos del cliente
                return response()->json([
                    'data' => $cliente,
                    'mensaje' => 'Cliente encontrado exitosamente'
                ], 200);
            } else {
                // Retornar mensaje de cliente no encontrado
                return response()->json([
                    'mensaje' => 'Cliente no encontrado'
                ], 404);
            }
        } catch (\Exception $e) {
            // Manejo de excepciones
            return response()->json([
                'mensaje' => 'Error al buscar el cliente'
            ], 500);
        }
    }

    /**Cantidad de clientes */
    public function cantidadClientes()
    {
        $cantidad = Clientes::count();

        return response()->json([
            'cantidad_clientes' => $cantidad,
            'status' => 200
        ], 200);
    }


    public function cantidad()
    {
        $dbClientes = ClientesResource::collection(Clientes::all());
        $cantidadCliente = 0;

        foreach ($dbClientes as $cliente) {
            $cantidadCliente++;
        }

        dd($cantidadCliente);
    }

    public function reporteClientes()
    {
        // Obtener los clientes y la fecha actual
        $clientes = Clientes::orderBy('empresa')->get();
        $fechaActual = Carbon::now('America/Costa_Rica');

        // Formatear números de teléfono
        $clientes->transform(function ($cliente) {
            $cliente->telefono = $this->formatearNumeroCelular($cliente->telefono);
            return $cliente;
        });

        // Renderizar la vista Blade y obtener su contenido HTML
        $html = View::make('clientes', [
            'clientes' => $clientes,
            'fechaActual' => $fechaActual,
        ])->render();


        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);

        // Inicializar Dompdf
        $dompdf = new Dompdf($options);

        // Cargar el HTML en Dompdf
        $dompdf->loadHtml($html);

        // Establecer el tamaño del papel y la orientación
        $dompdf->setPaper('A4', 'landscape');

        $nombreArchivo = 'clientes_' . $fechaActual . '.pdf';

        // Renderizar el PDF
        $dompdf->render();

        // Devolver el PDF al navegador
        return $dompdf->stream($nombreArchivo);
    }

    private function formatearNumeroCelular($numero)
    {
        $numero = preg_replace('/[^\d]/', '', $numero);

        if (strlen($numero) == 8) {
            $numero = '506' . $numero;
        }

        if (strlen($numero) == 11 && strpos($numero, '506') === 0) {
            $parte1 = substr($numero, 0, 3);
            $parte2 = substr($numero, 3, 4);
            $parte3 = substr($numero, 7, 4);

            return "+$parte1 $parte2 $parte3";
        } else {
            return $numero;
        }
    }



    /**
     * Función creada para validar los tipos de datos que entran en el request
     * En dado caso que alguna validacion falle, se mostrará un mensaje con el motivo del fallo
     */
    public function validateData(Request $request)
    {
        //Reglas
        $rules = [
            'nombre' => 'required|string|max:60',
            'apellido1' => 'required|string|max:60',
            'apellido2' => 'required|string|max:60',
            'cedula' => 'nullable|string|max:20|',
            'email' => 'nullable|email',
            'telefono' => 'nullable|string|max:20',
            'empresa' => 'nullable|string|max:70',
            'departamento' => 'nullable|string|max:70',
            'comentarios' => 'nullable|string',
        ];

        //Mensajes
        $messages = [
            'nombre.required' => 'El campo nombre es obligatorio.',
            'apellido1.required' => 'El campo apellido1 es obligatorio.',
            'apellido2.required' => 'El campo apellido2 es obligatorio.',
            'cedula.required' => 'El campo cedula es obligatorio.',
            'telefono.required' => 'El campo telefono es obligatorio.',

            'nombre.string' => 'El campo nombre debe ser una cadena de caracteres.',
            'apellido1.string' => 'El campo apellido1 debe ser una cadena de caracteres.',
            'apellido2.string' => 'El campo apellido2 debe ser una cadena de caracteres.',
            'cedula.string' => 'El campo cedula debe ser una cadena de caracteres.',
            'telefono.string' => 'El campo telefono debe ser una cadena de caracteres.',
            'empresa.string' => 'El campo empresa debe ser una cadena de caracteres.',
            'departamento.string' => 'El campo departamento debe ser una cadena de caracteres.',

            'nombre.max' => 'El campo nombre no debe exceder los 60 caracteres.',
            'apellido1.max' => 'El campo apellido1 no debe exceder los 60 caracteres.',
            'apellido2.max' => 'El campo apellido2 no debe exceder los 60 caracteres.',
            'cedula.max' => 'El campo cedula no debe exceder los 20 caracteres.',
            'telefono.max' => 'El campo telefono no debe exceder los 70 caracteres.',
            'empresa.max' => 'El campo empresa no debe exceder los 70 caracteres.',
            'departamento.max' => 'El departamento nombre no debe exceder los 255 caracteres.',
        ];

        //Validacion
        $validator = Validator::make($request->all(), $rules, $messages);

        //Si la validación falla, muestra el error.
        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        return true;
    }
}
