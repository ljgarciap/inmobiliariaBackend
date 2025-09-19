<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropiedadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'detalle' => $this->detalle,
            'descripcion' => $this->descripcion,
            'ciudad' => $this->ciudad->nombre,
            'habitaciones' => $this->habitaciones,
            'banios' => $this->banios,
            'tipo_transaccion' => $this->tipo_transaccion,
            'precio' => $this->precio,
            'precio_arriendo' => $this->precio_arriendo,
            'precio_venta' => $this->precio_venta,
            'caracteristicas' => $this->caracteristicas->pluck('nombre'),
            'imagenes' => $this->imagenes->map(function ($imagen) {
                return [
                    'id' => $imagen->id,
                    'url' => asset('storage/propiedades/' . $imagen->ruta_imagen),
                    'principal' => $imagen->principal,
                    'ruta_imagen' => $imagen->ruta_imagen
                ];
            }),
            'imagen_principal' => $this->imagenes->where('principal', true)->first()
            ? asset('storage/propiedades/' . $this->imagenes->where('principal', true)->first()->ruta_imagen)
            : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
