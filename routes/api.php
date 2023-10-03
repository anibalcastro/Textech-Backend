<?php

use App\Http\Controllers\Api\V1\AbonosController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ClientesController;
use App\Http\Controllers\Api\V1\MedicionesController;
use App\Http\Controllers\Api\V1\EmpresasController;
use App\Http\Controllers\Api\V1\OrdenPedidoController;
use App\Http\Controllers\Api\V1\ProductosController;


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
Route::apiResource('v1/clientes', ClientesController::class)->only((['index', 'show', 'destroy']))->middleware('jwt.auth');
Route::post('v1/clientes/registrar', [App\Http\Controllers\Api\V1\ClientesController::class, 'registrarCliente'])->middleware('jwt.auth');
Route::post('v1/clientes/editar/{id}', [App\Http\Controllers\Api\V1\ClientesController::class, 'modificarCliente'])->middleware('jwt.auth');
Route::post('v1/clientes/{id}', [App\Http\Controllers\Api\V1\ClientesController::class, 'obtenerCliente'])->middleware('jwt.auth');
Route::post('v1/clientes/eliminar/{id_cliente}', [App\Http\Controllers\Api\V1\ClientesController::class, 'eliminarCliente'])->middleware('jwt.auth');

/***************** */

/***************** */
//Rutas de mediciones
Route::apiResource('v1/mediciones', MedicionesController::class)->only((['index', 'show', 'destroy']))->middleware('jwt.auth');
Route::post('v1/mediciones/registrar', [App\Http\Controllers\Api\V1\MedicionesController::class, 'registrarMedida'])->middleware('jwt.auth');
Route::post('v1/mediciones/editar/{id}', [App\Http\Controllers\Api\V1\MedicionesController::class, 'modificarMedida'])->middleware('jwt.auth');
Route::post('v1/mediciones/{id_cliente}', [App\Http\Controllers\Api\V1\MedicionesController::class, 'retornarMedicionesCliente'])->middleware('jwt.auth');
Route::get ('v1/mediciones/clientes', [App\Http\Controllers\Api\V1\MedicionesController::class, 'show'])->middleware('jwt.auth');
Route::post('v1/mediciones/eliminar/{id_medicion}',[App\Http\Controllers\Api\V1\MedicionesController::class, 'eliminarMedida'])->middleware('jwt.auth');
/***************** */

/***************** */
//Login
Route::post('v1/login', [App\Http\Controllers\Api\V1\LoginController::class, 'login']);
/***************** */

/***************** */
//Rutas de empresas
Route::apiResource('v1/empresas', EmpresasController::class)->only((['index', 'mostrar', 'destruir']))->middleware('jwt.auth');
Route::get('v1/empresas/cantidad', [App\Http\Controllers\Api\V1\EmpresasController::class, 'cantidadEmpresas'])->middleware('jwt.auth');
Route::post('v1/empresas/registrar', [App\Http\Controllers\Api\V1\EmpresasController::class, 'registrarEmpresa'])->middleware('jwt.auth');
Route::post('v1/empresas/editar/{id}', [App\Http\Controllers\Api\V1\EmpresasController::class, 'modificarEmpresa'])->middleware('jwt.auth');
Route::post('v1/empresas/eliminar/{id_empresa}', [App\Http\Controllers\Api\V1\EmpresasController::class, 'eliminarEmpresa'])->middleware('jwt.auth');
/***************** */


/***************** */
//Ruta de productos
Route::apiResource('v1/productos', ProductosController::class)->only((['index']))->middleware('jwt.auth');
Route::get('v1/productos/cantidad', [App\Http\Controllers\Api\V1\ProductosController::class, 'cantidadProductos'])->middleware('jwt.auth');
Route::post('v1/productos/registrar', [App\Http\Controllers\Api\V1\ProductosController::class, 'registrarProducto'])->middleware('jwt.auth');
Route::post('v1/productos/editar/{id_producto}', [App\Http\Controllers\Api\V1\ProductosController::class, 'modificarProducto'])->middleware('jwt.auth');
Route::post('v1/productos/editarprecio/{id_producto}', [App\Http\Controllers\Api\V1\ProductosController::class, 'modifcarPrecioProducto'])->middleware('jwt.auth');
Route::delete('v1/productos/eliminar/{id_producto}', [App\Http\Controllers\Api\V1\ProductosController::class, 'eliminarProducto'])->middleware('jwt.auth');

/***************** */
//Ruta de orden de pedidos
Route::apiResource('v1/ordenes', OrdenPedidoController::class)->only((['index']))->middleware('jwt.auth');
Route::get('v1/ordenes/cantidad', [App\Http\Controllers\Api\V1\OrdenPedidoController::class, 'cantidadOrdenEstado'])->middleware('jwt.auth');
Route::get('v1/ordenes/{id_orden}', [App\Http\Controllers\Api\V1\OrdenPedidoController::class, 'ordenPedidoDetalleFactura'])->middleware('jwt.auth');
Route::post('v1/ordenes/registrar', [App\Http\Controllers\Api\V1\OrdenPedidoController::class, 'crearOrden'])->middleware('jwt.auth');
Route::post('v1/ordenes/editar/{id_orden}', [App\Http\Controllers\Api\V1\OrdenPedidoController::class, 'modificarOrden'])->middleware('jwt.auth');
Route::post('v1/ordenes/editar/estado/{id_orden}', [App\Http\Controllers\Api\V1\OrdenPedidoController::class, 'actualizarEstadoPedido'])->middleware('jwt.auth');
Route::post('v1/ordenes/anular/{id_orden}', [App\Http\Controllers\Api\V1\OrdenPedidoController::class, 'anularOrden'])->middleware('jwt.auth');

/*************** */
//Ruta de abonos
Route::apiResource('v1/pagos', AbonosController::class)->only((['index']))->middleware('jwt.auth');
Route::get('v1/pagos/{id_factura}', [App\Http\Controllers\Api\V1\AbonosController::class, 'abonosPorFactura'])->middleware('jwt.auth');
Route::post('v1/pagos/registrar', [App\Http\Controllers\Api\V1\AbonosController::class, 'crearAbono'])->middleware('jwt.auth');
Route::post('v1/pagos/anular', [App\Http\Controllers\Api\V1\AbonosController::class, 'anularAbono'])->middleware('jwt.auth');


