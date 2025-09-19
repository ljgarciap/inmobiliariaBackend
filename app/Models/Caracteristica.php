<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caracteristica extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'icono'
    ];

    public function propiedades(): BelongsToMany
    {
        return $this->belongsToMany(Propiedad::class);
    }
}
