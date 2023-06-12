<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ClientesController as ClientesV1;
use App\Http\Controllers\Api\V1\MedicionesController as MedicionesV1;

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

Route::apiResource('v1/clientes', ClientesV1::class)->only((['index', 'show', 'destroy']))->middleware('auth:sanctum');
Route::post('v1/clientes/registrar', ClientesV1::class, 'registrarCliente')->middleware('auth:sanctum');
Route::post('v1/clientes/modificar', ClientesV1::class, 'modificarCliente')->middleware('auth:sanctum');
Route::get('v1/clientes/{id}', ClientesV1::class, 'clienteEspecifico')->middleware('auth:sanctum');


Route::apiResource('v1/mediciones', MedicionesV1::class)->only((['index', 'show', 'destroy']))->middleware('auth:sanctum');
Route::post('v1/login', [App\Http\Controllers\Api\V1\LoginController::class, 'login']);
