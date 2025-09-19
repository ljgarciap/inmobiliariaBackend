<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropiedadResource;
use App\Models\Propiedad;
use App\Models\PropiedadImagen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class PropiedadController extends Controller
{
    public function index(Request $request)
    {
        // $cacheKey = 'propiedades_' . md5(serialize($request->all()));

        //$propiedades = Cache::remember($cacheKey, 3600, function () use ($request) {
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

            $propiedades = $query->orderBy('created_at', 'desc')->paginate(12);

            return PropiedadResource::collection($propiedades);

            //return $query->orderBy('created_at', 'desc')->paginate(12);
        // });

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
            'imagenes.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
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

        if ($request->has('caracteristicas')) {
            $propiedad->caracteristicas()->sync($request->caracteristicas);
        }

        if ($request->hasFile('imagenes')) {
            $this->guardarImagenes($propiedad, $request->file('imagenes'));
        }

        //Cache::flush();

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
        'imagenes' => 'sometimes|array',
        'imagenes.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        'eliminar_imagenes' => 'sometimes|array',
        'eliminar_imagenes.*' => 'exists:propiedad_imagenes,id',
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

    if ($request->has('eliminar_imagenes')) {
        $this->eliminarImagenes($request->eliminar_imagenes);
    }

    if ($request->hasFile('imagenes')) {
        $this->guardarImagenes($propiedad, $request->file('imagenes'));
    }

    //Cache::flush();

    return new PropiedadResource($propiedad->load(['ciudad', 'caracteristicas', 'imagenes']));
    }

    public function destroy(Propiedad $propiedad)
    {
        if ($propiedad->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        foreach ($propiedad->imagenes as $imagen) {
            Storage::disk('propiedades')->delete($imagen->ruta_imagen);
            $imagen->delete();
        }

        $propiedad->delete();

        //Cache::flush();

        return response()->json(['message' => 'Propiedad eliminada correctamente']);
    }

    private function guardarImagenes(Propiedad $propiedad, array $imagenes)
    {
        foreach ($imagenes as $index => $imagen) {

            $nombreArchivo = time() . '_' . uniqid() . '.' . $imagen->getClientOriginalExtension();

            $ruta = $imagen->storeAs('', $nombreArchivo, 'propiedades');

            $esPrincipal = $index === 0 && $propiedad->imagenes->where('principal', true)->isEmpty();

            PropiedadImagen::create([
                'propiedad_id' => $propiedad->id,
                'ruta_imagen' => $ruta,
                'principal' => $esPrincipal
            ]);
        }
    }

    private function eliminarImagenes(array $imagenIds)
    {
        $imagenes = PropiedadImagen::whereIn('id', $imagenIds)->get();

        foreach ($imagenes as $imagen) {
            Storage::disk('propiedades')->delete($imagen->ruta_imagen);
            $imagen->delete();
        }
    }

    public function agregarImagenes(Request $request, Propiedad $propiedad)
    {
        $validator = Validator::make($request->all(), [
            'imagenes' => 'required|array',
            'imagenes.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($propiedad->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $this->guardarImagenes($propiedad, $request->file('imagenes'));

        return new PropiedadResource($propiedad->load(['ciudad', 'caracteristicas', 'imagenes']));
    }
}
