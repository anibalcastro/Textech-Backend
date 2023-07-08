<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        try {
            $this->validarLogin($request);

            if (Auth::attempt($request->only('email', 'password'))) {
                $user = Auth::user(); // Obtén el usuario autenticado actualmente

                return response()->json([
                    'token' => JWTAuth::fromUser($user), // Genera el JWT utilizando el usuario
                    'mensaje' => 'Success',
                    'role' => $user->role,
                    'status' => 200
                ]);
            }

            return response()->json([
                'mensaje' => 'No se ha podido autenticar',
                'status' => 404,
            ], 401);
        } catch (Exception $e) {
            // Captura cualquier excepción lanzada durante el proceso de autenticación
            return response()->json([
                'mensaje' => 'Se produjo un error en el servidor',
                'status' => 500,
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ], 500);
        }
    }

    /**Funcion para retornar el nombre del usuario que desea ingresar */
    public function retornarNombre($email)
    {
        $usuario = User::where('email', $email)->first();

        if ($usuario) {
            return $usuario->name;
        } else {
            return 'Textec'; // O puedes devolver un valor predeterminado si el usuario no existe
        }
    }

    public function validarLogin(Request $request)
    {
        return $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);
    }
}
