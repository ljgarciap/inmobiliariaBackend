<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Caracteristica extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'icono'
    ];

    public function propiedades(): BelongsToMany
    {
        // CORREGIR: Especificar la tabla pivote
        return $this->belongsToMany(Propiedad::class, 'propiedades_caracteristicas');
    }
}
