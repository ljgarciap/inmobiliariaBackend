<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ciudad extends Model
{
    use HasFactory;

    protected $table = 'ciudades';

    protected $fillable = [
        'nombre',
        'pais'
    ];

    public function propiedades(): HasMany
    {
        return $this->hasMany(Propiedad::class);
    }
}
