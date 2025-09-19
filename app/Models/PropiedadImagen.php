<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropiedadImagen extends Model
{
    use HasFactory;

    protected $table = 'propiedad_imagenes';

    protected $fillable = [
        'propiedad_id',
        'ruta_imagen',
        'principal'
    ];

    public function propiedad(): BelongsTo
    {
        return $this->belongsTo(Propiedad::class);
    }
}
