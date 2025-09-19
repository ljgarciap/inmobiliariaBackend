<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropiedadResource;
use App\Models\Propiedad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class PropiedadController extends Controller
{
    public function index(Request $request)
    {
        // Cache key basada en los parámetros de filtro
        $cacheKey = 'propiedades_' . md5(serialize($request->all()));

        // Intentar obtener del cache, sino ejecutar la consulta
        $propiedades = Cache::remember($cacheKey, 3600, function () use ($request) {
            $query = Propiedad::with(['ciudad', 'caracteristicas', 'imagenes', 'user']);

            // Filtro por ciudad
            if ($request->has('ciudad_id')) {
                $query->where('ciudad_id', $request->ciudad_id);
            }

            // Filtro por tipo de transacción
            if ($request->has('tipo_transaccion')) {
                $query->where('tipo_transaccion', $request->tipo_transaccion);
            }

            // Filtro por rango de precio
            if ($request->has('precio_minimo') && $request->has('precio_maximo')) {
                $priceField = $request->tipo_transaccion === 'arriendo'
                    ? 'precio_arriendo'
                    : 'precio_venta';

                $query->whereBetween($priceField, [
                    $request->precio_minimo,
                    $request->precio_maximo
                ]);
            }

            // Filtro por número de habitaciones (búsqueda múltiple)
            if ($request->has('habitaciones')) {
                $habitaciones = is_array($request->habitaciones)
                    ? $request->habitaciones
                    : explode(',', $request->habitaciones);

                $query->whereIn('habitaciones', $habitaciones);
            }

            // Filtro por características
            if ($request->has('caracteristicas')) {
                $caracteristicas = is_array($request->caracteristicas)
                    ? $request->caracteristicas
                    : explode(',', $request->caracteristicas);

                $query->whereHas('caracteristicas', function ($q) use ($caracteristicas) {
                    $q->whereIn('caracteristicas.id', $caracteristicas);
                });
            }

            return $query->orderBy('created_at', 'desc')->paginate(12);
        });

        return PropiedadResource::collection($propiedades);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detalle' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'ciudad_id' => 'required|exists:ciudades,id',
            'habitaciones' => 'required|integer|min:0',
            'banios' => 'required|integer|min:0',
            'tipo_transaccion' => 'required|in:arriendo,venta',
            'precio_arriendo' => 'required_if:tipo_transaccion,arriendo|nullable|numeric|min:0',
            'precio_venta' => 'required_if:tipo_transaccion,venta|nullable|numeric|min:0',
            'caracteristicas' => 'array',
            'caracteristicas.*' => 'exists:caracteristicas,id',
            'imagenes' => 'array',
            'imagenes.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $propiedad = Propiedad::create([
            'detalle' => $request->detalle,
            'descripcion' => $request->descripcion,
            'ciudad_id' => $request->ciudad_id,
            'habitaciones' => $request->habitaciones,
            'banios' => $request->banios,
            'tipo_transaccion' => $request->tipo_transaccion,
            'precio_arriendo' => $request->tipo_transaccion === 'arriendo' ? $request->precio_arriendo : null,
            'precio_venta' => $request->tipo_transaccion === 'venta' ? $request->precio_venta : null,
            'user_id' => auth()->id(),
        ]);

        // Sincronizar características
        if ($request->has('caracteristicas')) {
            $propiedad->caracteristicas()->sync($request->caracteristicas);
        }

        // Procesar imágenes
        if ($request->hasFile('imagenes')) {
            // TODO Lógica para guardar imágenes
        }

        // Limpiar cache de propiedades
        Cache::flush();

        return new PropiedadResource($propiedad->load(['ciudad', 'caracteristicas', 'imagenes']));
    }

    public function show(Propiedad $propiedad)
    {
        return new PropiedadResource($propiedad->load(['ciudad', 'caracteristicas', 'imagenes', 'user']));
    }

    public function update(Request $request, Propiedad $propiedad)
    {
    if ($propiedad->user_id !== auth()->id()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $validator = Validator::make($request->all(), [
        'detalle' => 'sometimes|required|string|max:255',
        'descripcion' => 'sometimes|required|string',
        'ciudad_id' => 'sometimes|required|exists:ciudades,id',
        'habitaciones' => 'sometimes|required|integer|min:0',
        'banios' => 'sometimes|required|integer|min:0',
        'tipo_transaccion' => 'sometimes|required|in:arriendo,venta',
        'precio_arriendo' => 'required_if:tipo_transaccion,arriendo|nullable|numeric|min:0',
        'precio_venta' => 'required_if:tipo_transaccion,venta|nullable|numeric|min:0',
        'caracteristicas' => 'sometimes|array',
        'caracteristicas.*' => 'exists:caracteristicas,id',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    $data = $request->only([
        'detalle', 'descripcion', 'ciudad_id', 'habitaciones', 'banios',
        'tipo_transaccion'
    ]);

    if ($request->has('tipo_transaccion')) {
        $data['precio_arriendo'] = $request->tipo_transaccion === 'arriendo' ? $request->precio_arriendo : null;
        $data['precio_venta'] = $request->tipo_transaccion === 'venta' ? $request->precio_venta : null;
    } else {

        $data['precio_arriendo'] = $propiedad->precio_arriendo;
        $data['precio_venta'] = $propiedad->precio_venta;
    }

    $propiedad->update($data);

    if ($request->has('caracteristicas')) {
        $propiedad->caracteristicas()->sync($request->caracteristicas);
    }

    Cache::flush();

    return new PropiedadResource($propiedad->load(['ciudad', 'caracteristicas', 'imagenes']));
    }

    public function destroy(Propiedad $propiedad)
    {
        // Verificar que el usuario es el propietario
        if ($propiedad->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $propiedad->delete();

        // Limpiar cache de propiedades
        Cache::flush();

        return response()->json(['message' => 'Propiedad eliminada correctamente']);
    }
}
