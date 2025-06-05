<?php

namespace App\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\Clientes;
use App\Models\Inventario;
use App\Models\Mediciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;


class ReportesController extends Controller
{

    /**Funcion que genera el detalle de la reparacion */
    public function generarDetalleReparacion($id_reparacion)
    {
        $reparacion = DB::table('reparacion_prendas as rp')
            ->join('facturas as f', 'f.id', '=', 'rp.id_factura')
            ->join('empresas as e', 'e.id', '=', 'rp.id_empresa')
            ->select('rp.id', 'rp.titulo', 'e.nombre_empresa', 'e.telefono_encargado', 'rp.estado', 'rp.fecha', 'f.cajero')
            ->where('rp.id', $id_reparacion)
            ->first();

        $detallePedido = DB::table('reparacion_prendas as rp')
            ->join('detalle_reparacion_prendas as dp', 'dp.id_reparacion', '=', 'rp.id')
            ->join('productos as p', 'p.id', '=', 'dp.id_producto')
            ->select('p.nombre_producto', 'dp.cantidad', 'dp.descripcion', 'dp.precio_unitario', 'dp.subtotal')
            ->where('rp.id', $id_reparacion)
            ->get();

        $facturaPedido = DB::table('reparacion_prendas as rp')
            ->join('facturas as f', 'f.id', '=', 'rp.id_factura')
            ->select('f.subtotal', 'f.iva', 'f.monto', 'f.saldo_restante')
            ->where('rp.id', $id_reparacion)
            ->first();

        $html = View::make('detalle_reparacion', [
            'encabezadoPedido' => $reparacion,
            'detalle' => $detallePedido,
            'factura' => $facturaPedido
        ])->render();


        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);

        // Inicializar Dompdf
        $dompdf = new Dompdf($options);

        // Cargar el HTML en Dompdf
        $dompdf->loadHtml($html);

        // Establecer el tamaño del papel y la orientación
        $dompdf->setPaper('A4', 'portrait');

        $nombreArchivo = 'Reparacion_' . $reparacion->nombre_empresa;

        // Renderizar el PDF
        $dompdf->render();

        $pdfContent = $dompdf->output();

        // Guardar el PDF temporalmente en el servidor
        $rutaArchivo = 'reportes/' . $nombreArchivo; // ruta en el nuevo sistema de archivos
        Storage::disk('reportes')->put($nombreArchivo, $pdfContent);

        // Construir la URL del archivo para descargar
        $urlDescarga = url('storage/' . $rutaArchivo);

        // Devolver la URL del archivo para descargar
        return response()->json([
            'download_url' => $urlDescarga,
            'nombreArchivo' => $nombreArchivo,
        ]);
    }

    /**Funcion que genera el detalle del pedido */
    public function generarDetallePedido($id_pedido)
    {
        try {
            // 1. Validar que exista la orden
            $encabezadoPedido = DB::table('orden_pedido as op')
                ->select(
                    'op.id',
                    'op.proforma',
                    'op.titulo',
                    'e.nombre_empresa',
                    'e.telefono_encargado',
                    'op.estado',
                    'op.fecha_orden',
                    'f.cajero'
                )
                ->join('facturas as f', 'f.id', '=', 'op.id_factura')
                ->join('empresas as e', 'e.id', '=', 'op.id_empresa')
                ->where('op.id', $id_pedido)
                ->first();

            if (!$encabezadoPedido) {
                return response()->json(['error' => 'Pedido no encontrado'], 404);
            }

            // 2. Obtener detalle del pedido
            $detallePedido = DB::table('detalle_pedido as dp')
                ->select('p.nombre_producto', 'dp.cantidad', 'dp.descripcion', 'dp.precio_unitario', 'dp.subtotal')
                ->join('productos as p', 'p.id', '=', 'dp.id_producto')
                ->where('dp.id_pedido', $id_pedido)
                ->get();

            // 3. Obtener factura
            $facturaPedido = DB::table('orden_pedido as op')
                ->select('f.subtotal', 'f.iva', 'f.monto', 'f.saldo_restante')
                ->join('facturas as f', 'f.id', '=', 'op.id_factura')
                ->where('op.id', $id_pedido)
                ->first();

            // 4. Renderizar la vista
            $html = View::make('detalle_pedido', [
                'encabezadoPedido' => $encabezadoPedido,
                'detalle' => $detallePedido,
                'factura' => $facturaPedido
            ])->render();

            // 5. Configurar DomPDF
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // 6. Sanitizar nombre del archivo
            $nombreEmpresa = str_replace(' ', '_', $encabezadoPedido->nombre_empresa);
            $nombreArchivo = 'Detalle_pedido_' . $nombreEmpresa . '.pdf';
            $rutaArchivo = 'reportes/' . $nombreArchivo;

            // 7. Guardar el archivo
            $pdfContent = $dompdf->output();
            Storage::disk('reportes')->put($nombreArchivo, $pdfContent);

            // 8. Generar URL
            $urlDescarga = url('storage/' . $rutaArchivo);

            // 9. Retornar respuesta
            return response()->json([
                'download_url' => $urlDescarga,
                'nombreArchivo' => $nombreArchivo,
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error generando PDF de pedido $id_pedido: " . $e->getMessage());
            return response()->json(['error' => 'Error al generar el PDF'], 500);
        }
    }

    /**Funcion que genera detalle de pagos de las reparaciones */
    public function detallePagoReparacion($id_reparacion)
    {
        try {
            $reparacion = DB::table('reparacion_prendas as rp')
                ->join('facturas as f', 'f.id', '=', 'rp.id_factura')
                ->join('empresas as e', 'e.id', '=', 'rp.id_empresa')
                ->select('rp.id', 'rp.titulo', 'e.nombre_empresa', 'e.telefono_encargado', 'rp.estado', 'rp.fecha', 'f.cajero')
                ->where('rp.id', $id_reparacion)
                ->first();

            $detallePedido = DB::table('reparacion_prendas as rp')
                ->join('detalle_reparacion_prendas as dp', 'dp.id_reparacion', '=', 'rp.id')
                ->join('productos as p', 'p.id', '=', 'dp.id_producto')
                ->select('p.nombre_producto', 'dp.cantidad', 'dp.descripcion', 'dp.precio_unitario', 'dp.subtotal')
                ->where('rp.id', $id_reparacion)
                ->get();

            $facturaPedido = DB::table('reparacion_prendas as rp')
                ->join('facturas as f', 'f.id', '=', 'rp.id_factura')
                ->select('f.subtotal', 'f.iva', 'f.monto', 'f.saldo_restante')
                ->where('rp.id', $id_reparacion)
                ->first();

            $abonosReparacion = DB::table('reparacion_prendas as rp')
                ->join('facturas as f', 'f.id', '=', 'rp.id_factura')
                ->join('abonos as a', 'a.factura_id', '=', 'f.id')
                ->select('a.created_at', 'a.estado', 'a.metodo_pago', 'a.monto', 'a.cajero')
                ->where('rp.id', $id_reparacion)
                ->get();

            $html = View::make('detalle_pago_reparacion', [
                'encabezadoPedido' => $reparacion,
                'detalle' => $detallePedido,
                'factura' => $facturaPedido,
                'pagos' => $abonosReparacion
            ])->render();


            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);

            // Inicializar Dompdf
            $dompdf = new Dompdf($options);

            // Cargar el HTML en Dompdf
            $dompdf->loadHtml($html);

            // Establecer el tamaño del papel y la orientación
            $dompdf->setPaper('A4', 'portrait');

            $nombreArchivo = 'Pago_reparacion.pdf';

            // Renderizar el PDF
            $dompdf->render();


            $pdfContent = $dompdf->output();

            // Guardar el PDF temporalmente en el servidor
            $rutaArchivo = 'reportes/' . $nombreArchivo; // ruta en el nuevo sistema de archivos
            Storage::disk('reportes')->put($nombreArchivo, $pdfContent);

            // Construir la URL del archivo para descargar
            $urlDescarga = url('storage/' . $rutaArchivo);

            // Devolver la URL del archivo para descargar
            return response()->json([
                'download_url' => $urlDescarga,
                'nombreArchivo' => $nombreArchivo,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**Funcion que genera detalle de pagos de los pedidos. */
    public function detallePagoPedido($id_pedido)
    {
        $encabezadoPedido = DB::table('orden_pedido as op')
            ->select('op.id', 'op.proforma', 'op.titulo', 'e.nombre_empresa', 'e.telefono_encargado', 'op.estado', 'op.fecha_orden', 'f.cajero')
            ->join('facturas as f', 'f.id', '=', 'op.id_factura')
            ->join('empresas as e', 'e.id', '=', 'op.id_empresa')
            ->where('op.id', '=', $id_pedido)
            ->first();

        $detallePedido = DB::table('orden_pedido as op')
            ->select('p.nombre_producto', 'dp.cantidad', 'dp.descripcion', 'dp.precio_unitario', 'dp.subtotal')
            ->join('detalle_pedido as dp', 'dp.id_pedido', '=', 'op.id')
            ->join('productos as p', 'p.id', '=', 'dp.id_producto')
            ->where('op.id', '=', $id_pedido)
            ->get();

        $facturaPedido = DB::table('orden_pedido as op')
            ->select('f.subtotal', 'f.iva', 'f.monto', 'f.saldo_restante')
            ->join('facturas as f', 'f.id', '=', 'op.id_factura')
            ->where('op.id', '=', $id_pedido)
            ->first();


        $abonosOrdenPedido = DB::table('orden_pedido as op')
            ->join('facturas as f', 'f.id', '=', 'op.id_factura')
            ->join('abonos as a', 'a.factura_id', '=', 'f.id')
            ->select('a.created_at', 'a.estado', 'a.metodo_pago', 'a.monto', 'a.cajero')
            ->where('op.id', $id_pedido)
            ->get();

        $html = View::make('detalle_pago_pedido', [
            'encabezadoPedido' => $encabezadoPedido,
            'detalle' => $detallePedido,
            'factura' => $facturaPedido,
            'pagos' => $abonosOrdenPedido
        ])->render();


        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);

        // Inicializar Dompdf
        $dompdf = new Dompdf($options);

        // Cargar el HTML en Dompdf
        $dompdf->loadHtml($html);

        // Establecer el tamaño del papel y la orientación
        $dompdf->setPaper('A4', 'portrait');

        $nombreArchivo = 'Pago_orden_pedido_' . $encabezadoPedido->nombre_empresa . '.pdf';

        // Renderizar el PDF
        $dompdf->render();

        $pdfContent = $dompdf->output();

        // Guardar el PDF temporalmente en el servidor
        $rutaArchivo = 'reportes/' . $nombreArchivo; // ruta en el nuevo sistema de archivos
        Storage::disk('reportes')->put($nombreArchivo, $pdfContent);

        // Construir la URL del archivo para descargar
        $urlDescarga = url('storage/' . $rutaArchivo);

        // Devolver la URL del archivo para descargar
        return response()->json([
            'download_url' => $urlDescarga,
            'nombreArchivo' => $nombreArchivo,
        ]);
    }

    /**Metodo para generar detalle de pago */
    public function generarDetallePago($tipo, $id)
    {

        if ($tipo === "reparaciones") {
            return $this->detallePagoReparacion($id);
        } else {
            return $this->detallePagoPedido($id);
        }
    }

    /** Función para generar el reporte de clientes */
    public function reporteClientes()
    {
        try {
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

            // Configurar opciones de Dompdf
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);

            // Inicializar Dompdf
            $dompdf = new Dompdf($options);

            // Cargar el HTML en Dompdf
            $dompdf->loadHtml($html);

            // Establecer el tamaño del papel y la orientación
            $dompdf->setPaper('A4', 'landscape');

            // Renderizar el PDF
            $dompdf->render();

            // Obtener el contenido del PDF
            $pdfContent = $dompdf->output();

            // Guardar el PDF temporalmente en el servidor
            $nombreArchivo = 'clientes_reporte.pdf';
            $rutaArchivo = 'reportes/' . $nombreArchivo; // ruta en el nuevo sistema de archivos
            Storage::disk('reportes')->put($nombreArchivo, $pdfContent);

            // Construir la URL del archivo para descargar
            $urlDescarga = url('storage/' . $rutaArchivo);

            // Devolver la URL del archivo para descargar
            return response()->json([
                'download_url' => $urlDescarga,
                'nombreArchivo' => $nombreArchivo,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function vistaClientes()
    {
        // Obtener los clientes y la fecha actual
        $clientes = Clientes::orderBy('empresa')->get();

        return response()->json([
            'data' => $clientes,
            'status' => 200
        ], 200);
    }

    /**Mediciones de clientes de una empresa especifica */
    public function medidasClientes($nombre_empresa)
    {
        $clientes = Mediciones::select('clientes.nombre', 'clientes.apellido1', 'clientes.apellido2', 'mediciones.articulo', 'clientes.empresa', 'mediciones.created_at')
            ->join('clientes', 'clientes.id', '=', 'mediciones.id_cliente')
            ->where('clientes.empresa', $nombre_empresa)
            ->get();

        $fechaActual = Carbon::now('America/Costa_Rica');

        //return view('mediciones_clientes', ['clientes' => $clientes, 'fechaActual' => $fechaActual]);

        // Renderizar la vista Blade y obtener su contenido HTML
        $html = View::make('mediciones_clientes', [
            'clientes' => $clientes,
            'fechaActual' => $fechaActual,
        ])->render();

        info($html);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);

        // Inicializar Dompdf
        $dompdf = new Dompdf($options);

        // Cargar el HTML en Dompdf
        $dompdf->loadHtml($html);

        // Establecer el tamaño del papel y la orientación
        $dompdf->setPaper('A4', 'landscape');

        $nombreArchivo = $nombre_empresa . '_clientes_' . '.pdf';

        // Renderizar el PDF
        $dompdf->render();

        $pdfContent = $dompdf->output();

        // Guardar el PDF temporalmente en el servidor
        $rutaArchivo = 'reportes/' . $nombreArchivo; // ruta en el nuevo sistema de archivos
        Storage::disk('reportes')->put($nombreArchivo, $pdfContent);

        // Construir la URL del archivo para descargar
        $urlDescarga = url('storage/' . $rutaArchivo);

        // Devolver la URL del archivo para descargar
        return response()->json([
            'download_url' => $urlDescarga,
            'nombreArchivo' => $nombreArchivo,
        ]);
    }

    public function vistaMedidasClientes()
    {
        $clientes = Mediciones::select('clientes.nombre', 'clientes.apellido1', 'clientes.apellido2', 'mediciones.articulo', 'clientes.empresa', 'mediciones.created_at')
            ->join('clientes', 'clientes.id', '=', 'mediciones.id_cliente')
            ->get();

        return response()->json([
            'data' => $clientes,
            'status' => 200
        ], 200);
    }

    /**Inventario actual de la empresa */
    public function reporteInventario()
    {
        // Obtener todos los registros del inventario
        $inventario = DB::table('inventario as i')
            ->select('i.id', 'i.nombre_producto', 'i.cantidad', 'i.color', 'c.nombre_categoria', 'p.nombre as nombre_proveedor', 'i.comentario')
            ->leftJoin('categorias as c', 'c.id', '=', 'i.id_categoria')
            ->leftJoin('proveedores as p', 'p.id', '=', 'i.id_proveedor')
            ->get();


        $fechaActual = Carbon::now('America/Costa_Rica');


        // Renderizar la vista Blade y obtener su contenido HTML
        $html = View::make('inventario', [
            'inventario' => $inventario,
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

        // Renderizar el PDF
        $dompdf->render();

        $nombreArchivo = 'Inventario.pdf';

        $pdfContent = $dompdf->output();

        // Guardar el PDF temporalmente en el servidor
        $rutaArchivo = 'reportes/' . $nombreArchivo; // ruta en el nuevo sistema de archivos
        Storage::disk('reportes')->put($nombreArchivo, $pdfContent);

        // Construir la URL del archivo para descargar
        $urlDescarga = url('storage/' . $rutaArchivo);

        // Devolver la URL del archivo para descargar
        return response()->json([
            'download_url' => $urlDescarga,
            'nombreArchivo' => $nombreArchivo,
        ]);
    }


    public function vistaInventario()
    {
        // Obtener todos los registros del inventario
        $inventario = DB::table('inventario as i')
            ->select('i.id', 'i.nombre_producto', 'i.cantidad', 'i.color', 'c.nombre_categoria', 'p.nombre as nombre_proveedor', 'i.comentario')
            ->leftJoin('categorias as c', 'c.id', '=', 'i.id_categoria')
            ->leftJoin('proveedores as p', 'p.id', '=', 'i.id_proveedor')
            ->get();

        return response()->json([
            "data" => $inventario,
            "status" => 200
        ], 200);
    }

    /**Pdf de saldos pendientes */
    public function saldosPendientes()
    {
        $saldoPendiente = 0;

        $resultadoDB = DB::table('orden_pedido as op')
            ->leftJoin('facturas as f', 'f.id', '=', 'op.id_factura')
            ->leftJoin('empresas as e', 'e.id', '=', 'op.id_empresa')
            ->select('op.titulo', 'e.nombre_empresa', 'f.monto', 'f.saldo_restante', 'op.created_at')
            ->where('f.saldo_restante', '<>', 0)
            ->get();

        foreach ($resultadoDB as $item) {
            $saldoPendiente += $item->saldo_restante;
        }

        $fechaActual = Carbon::now('America/Costa_Rica');


        // Renderizar la vista Blade y obtener su contenido HTML
        $html = View::make('saldos_pendientes', [
            'saldos' => $resultadoDB,
            'fechaActual' => $fechaActual,
            'totalSaldo' => $saldoPendiente
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);

        // Inicializar Dompdf
        $dompdf = new Dompdf($options);

        // Cargar el HTML en Dompdf
        $dompdf->loadHtml($html);

        // Establecer el tamaño del papel y la orientación
        $dompdf->setPaper('A4', 'landscape');

        // Renderizar el PDF
        $dompdf->render();

        $nombreArchivo = 'ReporteSaldosPendientes.pdf';

        $pdfContent = $dompdf->output();

        // Guardar el PDF temporalmente en el servidor
        $rutaArchivo = 'reportes/' . $nombreArchivo; // ruta en el nuevo sistema de archivos
        Storage::disk('reportes')->put($nombreArchivo, $pdfContent);

        // Construir la URL del archivo para descargar
        $urlDescarga = url('storage/' . $rutaArchivo);

        // Devolver la URL del archivo para descargar
        return response()->json([
            'download_url' => $urlDescarga,
            'nombreArchivo' => $nombreArchivo,
        ]);
    }

    public function vistaSaldosPendientes()
    {
        $resultadoDB = DB::table('orden_pedido as op')
            ->leftJoin('facturas as f', 'f.id', '=', 'op.id_factura')
            ->leftJoin('empresas as e', 'e.id', '=', 'op.id_empresa')
            ->select('op.titulo', 'e.nombre_empresa', 'f.monto', 'f.saldo_restante', 'op.created_at')
            ->where('f.saldo_restante', '<>', 0)
            ->get();

        return response()->json([
            'data' => $resultadoDB,
            'status' => 200,
        ], 200);
    }

    /**Pdf de los productos mas vendidos */
    public function mejoresProductos()
    {
        //Consulta de los mejores productos en las ordenes de pedido.
        $resultadoDB = DB::table('orden_pedido as op')
            ->leftJoin('detalle_pedido as dp', 'dp.id_pedido', '=', 'op.id')
            ->leftJoin('productos as p', 'p.id', '=', 'dp.id_producto')
            ->select('p.nombre_producto', DB::raw('SUM(cantidad) as cantidad'))
            ->groupBy('p.nombre_producto')
            ->orderByDesc('cantidad')
            ->get();

        $fechaActual = Carbon::now('America/Costa_Rica');


        // Renderizar la vista Blade y obtener su contenido HTML
        $html = View::make('mejores_productos', [
            'productos' => $resultadoDB,
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

        // Renderizar el PDF
        $dompdf->render();

        $nombreArchivo = 'ReporteMejoresProductos.pdf';

        $pdfContent = $dompdf->output();

        // Guardar el PDF temporalmente en el servidor
        $rutaArchivo = 'reportes/' . $nombreArchivo; // ruta en el nuevo sistema de archivos
        Storage::disk('reportes')->put($nombreArchivo, $pdfContent);

        // Construir la URL del archivo para descargar
        $urlDescarga = url('storage/' . $rutaArchivo);

        // Devolver la URL del archivo para descargar
        return response()->json([
            'download_url' => $urlDescarga,
            'nombreArchivo' => $nombreArchivo,
        ]);
    }

    public function vistaMejoresProductos()
    {
        $consulta = DB::table('orden_pedido as op')
            ->leftJoin('detalle_pedido as dp', 'dp.id_pedido', '=', 'op.id')
            ->leftJoin('productos as p', 'p.id', '=', 'dp.id_producto')
            ->select('p.nombre_producto', DB::raw('SUM(cantidad) as cantidad'))
            ->groupBy('p.nombre_producto')
            ->orderByDesc('cantidad')
            ->get();


        return response()->json([
            'data' => $consulta,
            'status' => 200,
        ], 200);
    }

    public function ventas(Request $request)
    {
        $fechaInicio = $request->input('fechaInicio');
        $fechaFinal = $request->input('fechaFinal');
        $ventaTotal = 0;
        $montoPagado = 0;


        $resultados = DB::table('facturas as f')
            ->leftJoin('orden_pedido as op', 'op.id_factura', '=', 'f.id')
            ->leftJoin('reparacion_prendas as rp', 'rp.id_factura', '=', 'f.id')
            ->select(
                DB::raw('SUM(f.monto) as monto_facturado'),
                DB::raw('(SUM(f.monto) - SUM(f.saldo_restante)) as monto_pagado'),
                DB::raw('DATE(f.created_at) as fecha')
            )
            ->where('f.estado', '<>', 'Nula')
            ->groupBy(DB::raw('DATE(f.created_at)'))
            ->orderBy(DB::raw('DATE(f.created_at)'), 'desc')
            ->get();



        foreach ($resultados as $item) {
            $ventaTotal += $item->monto_facturado;
            $montoPagado += $item->monto_pagado;
        }



        $fechaActual = Carbon::now('America/Costa_Rica');


        // Renderizar la vista Blade y obtener su contenido HTML
        $html = View::make('ventas', [
            'ventas' => $resultados,
            'fechaActual' => $fechaActual,
            'fechaInicio' => $fechaInicio,
            'fechaFinal' => $fechaFinal,
            'totalVentas' => $ventaTotal,
            'montoPagado' => $montoPagado
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);

        // Inicializar Dompdf
        $dompdf = new Dompdf($options);

        // Cargar el HTML en Dompdf
        $dompdf->loadHtml($html);

        // Establecer el tamaño del papel y la orientación
        $dompdf->setPaper('A4', 'landscape');

        // Renderizar el PDF
        $dompdf->render();

        $nombreArchivo = 'ReporteVentas.pdf';

        $pdfContent = $dompdf->output();

        // Guardar el PDF temporalmente en el servidor
        $rutaArchivo = 'reportes/' . $nombreArchivo; // ruta en el nuevo sistema de archivos
        Storage::disk('reportes')->put($nombreArchivo, $pdfContent);

        // Construir la URL del archivo para descargar
        $urlDescarga = url('storage/' . $rutaArchivo);

        // Devolver la URL del archivo para descargar
        return response()->json([
            'download_url' => $urlDescarga,
            'nombreArchivo' => $nombreArchivo,
        ]);
    }

    public function vistaVentas()
    {
        $resultados = DB::table('facturas as f')
            ->leftJoin('orden_pedido as op', 'op.id_factura', '=', 'f.id')
            ->leftJoin('reparacion_prendas as rp', 'rp.id_factura', '=', 'f.id')
            ->select(
                DB::raw('SUM(f.monto) as monto_facturado'),
                DB::raw('(SUM(f.monto) - SUM(f.saldo_restante)) as monto_pagado'),
                DB::raw('DATE(f.created_at) as fecha')
            )
            ->where('f.estado', '<>', 'Nula')
            ->groupBy(DB::raw('DATE(f.created_at)'))
            ->orderBy(DB::raw('DATE(f.created_at)'), 'desc')
            ->get();


        return response()->json([
            'data' => $resultados,
            'status' => 200
        ], 200);
    }

    /**Formatea numero de telefono */
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
}
