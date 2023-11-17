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
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;

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

        $nombreArchivo = 'Reparacion ' . $reparacion->nombre_empresa;

        // Renderizar el PDF
        $dompdf->render();

        // Devolver el PDF al navegador
        return $dompdf->stream($nombreArchivo);
    }

    /**Funcion que genera el detalle del pedido */
    public function generarDetallePedido($id_pedido)
    {
        $encabezadoPedido = DB::table('orden_pedido as op')
            ->select('op.id', 'op.titulo', 'e.nombre_empresa', 'e.telefono_encargado', 'op.estado', 'op.fecha_orden', 'f.cajero')
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


        $html = View::make('detalle_pedido', [
            'encabezadoPedido' => $encabezadoPedido,
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

        $nombreArchivo = 'Detalle de pedido ' . $encabezadoPedido->nombre_empresa;

        // Renderizar el PDF
        $dompdf->render();

        // Devolver el PDF al navegador
        return $dompdf->stream($nombreArchivo);
    }

    /**Funcion que genera detalle de pagos de las reparaciones */
    public function detallePagoReparacion($id_reparacion)
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

        $nombreArchivo = 'Pagos de reparacion ' . $reparacion->nombre_empresa;

        // Renderizar el PDF
        $dompdf->render();

        // Devolver el PDF al navegador
        return $dompdf->stream($nombreArchivo);
    }

    /**Funcion que genera detalle de pagos de los pedidos. */
    public function detallePagoPedido($id_pedido)
    {
        $encabezadoPedido = DB::table('orden_pedido as op')
            ->select('op.id', 'op.titulo', 'e.nombre_empresa', 'e.telefono_encargado', 'op.estado', 'op.fecha_orden', 'f.cajero')
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

        $nombreArchivo = 'Pagos de orden de pedido ' . $encabezadoPedido->nombre_empresa;

        // Renderizar el PDF
        $dompdf->render();

        // Devolver el PDF al navegador
        return $dompdf->stream($nombreArchivo);
    }

    /**Metodo para generar detalle de pago */
    public function generarDetallePago($tipo, $id)
    {

        if ($tipo === "reparacion") {
            $this->detallePagoReparacion($id);
        } else {
            $this->detallePagoPedido($id);
        }
    }

    /**Funcion para genenerar los clientes */
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


        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);

        // Inicializar Dompdf
        $dompdf = new Dompdf($options);

        // Cargar el HTML en Dompdf
        $dompdf->loadHtml($html);

        // Establecer el tamaño del papel y la orientación
        $dompdf->setPaper('A4', 'landscape');

        $nombreArchivo = $nombre_empresa . ' clientes ' . $fechaActual . '.pdf';

        // Renderizar el PDF
        $dompdf->render();

        // Devolver el PDF al navegador
        return $dompdf->stream($nombreArchivo);
    }

    /**Inventario actual de la empresa */
    public function reporteInventario()
    {
        // Obtener todos los registros del inventario
        $inventario = Inventario::all();

        // Pasar los registros a la vista
        return view('inventario', ['inventario' => $inventario]);
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
