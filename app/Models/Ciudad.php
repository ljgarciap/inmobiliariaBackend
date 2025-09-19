<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ciudad extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'pais'
    ];

    public function propiedades(): HasMany
    {
        return $this->hasMany(Propiedad::class);
    }
}
