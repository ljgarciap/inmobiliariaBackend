<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Propiedad extends Model
{
    use HasFactory;

    protected $table = 'propiedades';

    protected $fillable = [
        'detalle',
        'descripcion',
        'ciudad_id',
        'habitaciones',
        'banios',
        'tipo_transaccion',
        'precio_arriendo',
        'precio_venta',
        'user_id'
    ];

    protected $casts = [
        'precio_arriendo' => 'decimal:2',
        'precio_venta' => 'decimal:2'
    ];

    public function ciudad(): BelongsTo
    {
        return $this->belongsTo(ciudad::class);
    }

    public function caracteristicas(): BelongsToMany
    {
        return $this->belongsToMany(Caracteristica::class);
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(PropiedadImagen::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPrecio()
    {
        return $this->tipo_transaccion === 'arriendo'
            ? $this->precio_arriendo
            : $this->precio_venta;
    }
}
