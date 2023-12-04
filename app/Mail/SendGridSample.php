<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Sichikawa\LaravelSendgridDriver\SendGrid;

class SendGridSample extends Mailable
{
    use Queueable, SerializesModels, SendGrid;

    protected $contenidoCorreo;

    /**
     * Create a new message instance.
     *
     * @param string $contenidoCorreo
     */
    public function __construct($contenidoCorreo)
    {
        $this->contenidoCorreo = $contenidoCorreo;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->view('emails.notificacion', ['contenidoCorreo' => $this->contenidoCorreo])
            ->subject('Notificaciones Textec')
            ->text('emails.test_text', ['contenidoCorreo' => $this->contenidoCorreo]);
    }
}
