<?php

namespace App\Http\Controllers\Api\V1;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Mail\SendGridSample;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{
    //Function to send notification
    public function sendEmail(Request $request)
    {
        $email = $request->input('email');
        $contenidoCorreo = $request->input('body');

        try {
            // Envía la notificación por correo electrónico
            Mail::to($email)->send(new SendGridSample($contenidoCorreo));

            // Puedes agregar lógica adicional si el correo se envía con éxito

            return response()->json(['mensaje' => 'Correo electrónico enviado con éxito']);
        } catch (\Exception $e) {
            // Manejar errores de envío de correo electrónico
            return response()->json(['error' => 'Error al enviar el correo electrónico', 'detalles' => $e->getMessage()], 500);
        }
    }
}
