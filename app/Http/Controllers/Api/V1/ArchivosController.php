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


    //Eliminar archivo de la base de datos y del servidor
    public function delete($name){
        // Buscar la entrada del archivo en la base de datos por su ruta
        $archivo = Archivos::where('file_path', $name)->first();

        if ($archivo) {
            // Obtener la ruta del archivo en el sistema de archivos
            $filePath = storage_path('app/public/archivos/' . $archivo->file_path);

            // Verificar si el archivo existe en el sistema de archivos
            if (file_exists($filePath)) {
                // Intentar eliminar el archivo del sistema de archivos
                if (unlink($filePath)) {
                    // Eliminar la entrada correspondiente de la base de datos
                    $archivo->delete();
                    return response()->json(['message' => 'Archivo y entrada de base de datos eliminados con éxito.'], 200);
                } else {
                    // Si la eliminación del archivo falla
                    return response()->json(['message' => 'Error al eliminar el archivo del sistema de archivos.'], 500);
                }
            } else {
                // Si el archivo no existe en el sistema de archivos
                // Eliminar la entrada correspondiente de la base de datos
                $archivo->delete();
                return response()->json(['message' => 'La entrada de la base de datos fue eliminada ya que el archivo no existe en el sistema de archivos.'], 200);
            }
        } else {
            // Si no se encuentra la entrada del archivo en la base de datos
            return response()->json(['message' => 'La entrada del archivo no existe en la base de datos.'], 404);
        }
    }


}
