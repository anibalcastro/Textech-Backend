<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicionesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_nombre' => $this->id_nombre,
            'articulo' => $this->articulo,
            'largo_inferior'=> $this->largo_inferior,
            'cintura_inferior' => $this->cintura_inferior,
            'cadera_inferior' => $this->cadera_inferior,
            'pierna_inferior' => $this->pierna_inferior,
            'rodilla_inferior' => $this->rodilla_inferior,
            'tiro_inferior' => $this->tiro_inferior,
            'espalda_superior' => $this->espalda_superior,
            'talle_espalda_superior' => $this->talle_espalda_superior,
            'talle_frente_superior' => $this->talle_frente_superior,
            'busto_superior' => $this->busto_superior,
            'cintura_superior' => $this->cintura_superior,
            'cadera_superior' => $this->cadera_superior,
            'largo_manga_superior' => $this->largo_manga_superior,
            'ancho_manga_superior' => $this->ancho_manga_superior,
            'largo_total_superior' => $this->largo_total_superior,
            'alto_pinza_superior' => $this->alto_pinza_superior,
            'fecha' => $this->fecha,
            'observaciones' => $this->observaciones,
            'ruedo_inferior' => $this->ruedo_inferior,
            'ruedo_inferior' => $this->ruedo_inferior,
            'ruedo_inferior' => $this->ruedo_inferior,

        ];
    }
}
