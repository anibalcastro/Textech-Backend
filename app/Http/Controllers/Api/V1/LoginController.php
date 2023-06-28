<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginController extends Controller
{
    public function login(Request $request)
    {

        $this->validarLogin($request);


        if (Auth::attempt($request->only('email', 'password'))) {

            $nombreUsuario = $this->retornarNombre($request->email);
            $user = Auth::user(); // Obtén el usuario autenticado actualmente

            return response()->json([

                'token' => JWTAuth::fromUser($user), // Genera el JWT utilizando el usuario,
                'mensaje' => 'Success'
            ]);
        }

        return response()->json([
            'mensaje' => 'No se ha podido autenticar'
        ], 401);
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
