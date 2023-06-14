<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ClientesController;
use App\Http\Controllers\Api\V1\MedicionesController;

use function Ramsey\Uuid\v1;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/***************** */
//Rutas de clientes
Route::apiResource('v1/clientes', ClientesController::class)->only((['index', 'show', 'destroy']))->middleware('auth:sanctum');
//Ruta para registrar un cliente
Route::post('v1/clientes/registrar', [App\Http\Controllers\Api\V1\ClientesController::class, 'registrarCliente'])->middleware('auth:sanctum');
//Ruta para modificar un cliente
Route::post('v1/clientes/editar/{id}', [App\Http\Controllers\Api\V1\ClientesController::class, 'modificarCliente'])->middleware('auth:sanctum');
//Ruta para obtener un cliente especifico.
Route::post('v1/clientes/{id}', [App\Http\Controllers\Api\V1\ClientesController::class, 'obtenerCliente'])->middleware('auth:sanctum');
/***************** */

//*** */
//Ruta de mediciones
Route::apiResource('v1/mediciones', MedicionesController::class)->only((['index', 'show', 'destroy']))->middleware('auth:sanctum');
Route::post('v1/mediciones/registrar', [App\Http\Controllers\Api\V1\MedicionesController::class, 'registrarMedida'])->middleware('auth:sanctum');
Route::post('v1/mediciones/editar/{id}', [App\Http\Controllers\Api\V1\MedicionesController::class, 'modificarMedida'])->middleware('auth:sanctum');
Route::post('v1/mediciones/{id_cliente}', [App\Http\Controllers\Api\V1\MedicionesController::class, 'retornarMedicionesCliente'])->middleware('auth:sanctum');
/***** */
//Login
Route::post('v1/login', [App\Http\Controllers\Api\V1\LoginController::class, 'login']);

/*** */
//Ruta de usuarios
