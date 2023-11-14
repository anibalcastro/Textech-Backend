<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\InventarioController;
use App\Http\Controllers\Api\V1\ClientesController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/mostrar-inventario', [InventarioController::class, 'mostrarInventario']);


