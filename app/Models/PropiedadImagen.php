<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropiedadImagen extends Model
{
    use HasFactory;

    protected $table = 'propiedad_imagenes';

    protected $fillable = [
        'propiedad_id',
        'ruta_imagen',
        'principal'
    ];

    protected $casts = [
        'principal' => 'boolean'
    ];

    public function propiedad(): BelongsTo
    {
        return $this->belongsTo(Propiedad::class);
    }

    public function getUrlAttribute()
    {
        return asset('storage/propiedades/' . $this->ruta_imagen);
    }
}
