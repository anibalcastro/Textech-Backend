<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Archivos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\OrdenPedido;

class ArchivosController extends Controller
{
    public function store(Request $request)
    {
        // Validar la solicitud
        $request->validate([
            'file' => 'required|mimes:pdf,xls,xlsx,doc,docx,png,jpg,heif,hevc|max:2048',
            'order_id' => 'required|exists:orden_pedido,id',
        ]);

        // Subir el archivo al disco 'archivos' con el mismo nombre original del archivo
        $file = $request->file('file');
        $filename = $file->getClientOriginalName(); // Obtener el nombre original del archivo

        // Reemplazar espacios por guiones bajos
        $filename = str_replace(' ', '_', $filename);

        $path = $file->storeAs('', $filename, 'archivos');

        // Guardar la ruta del archivo y el ID de la orden en la base de datos
        Archivos::create([
            'order_id' => $request->order_id,
            'file_path' => $path, // Guardar la ruta relativa devuelta por store
        ]);

        return response()->json(['message' => 'Archivo subido y almacenado con éxito.'], 201);
    }
}
