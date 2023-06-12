<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(Request $request){

        $this->validarLogin($request);

        if(Auth::attempt($request->only('email','password'))) {
            return response()->json([
                'token' => $request->user()->createToken($request->name)->plainTextToken,
                'mensaje' => 'Success'
            ]);
        }

        return response()->json([
            'mensaje' => 'No se ha podido autenticar'
        ], 401);
    }

    public function validarLogin(Request $request){
        return $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'name' => 'required'
        ]);
    }
}
