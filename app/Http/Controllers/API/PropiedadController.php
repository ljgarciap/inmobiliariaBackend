<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropiedadResource;
use App\Models\Propiedad;
use App\Models\PropiedadImagen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Services\GeocodingService;

class PropiedadController extends Controller
{
    public function index(Request $request)
    {
        $query = Propiedad::with(['ciudad', 'caracteristicas', 'imagenes', 'user']);

        if ($request->has('ciudad_id') && $request->ciudad_id != '') {
            $query->where('ciudad_id', $request->ciudad_id);
        }

        if ($request->has('tipo_transaccion')) {
            $query->where('tipo_transaccion', $request->tipo_transaccion);
        }

        if ($request->has('precio_minimo') || $request->has('precio_maximo')) {
            if ($request->has('tipo_transaccion')) {
                $priceField = $request->tipo_transaccion === 'arriendo'
                    ? 'precio_arriendo'
                    : 'precio_venta';

                if ($request->has('precio_minimo')) {
                    $query->where($priceField, '>=', $request->precio_minimo);
                }
                if ($request->has('precio_maximo')) {
                    $query->where($priceField, '<=', $request->precio_maximo);
                }
            } else {
                $query->where(function ($q) use ($request) {
                    if ($request->has('precio_minimo')) {
                        $q->where(function ($subQ) use ($request) {
                            $subQ->where('precio_arriendo', '>=', $request->precio_minimo)
                                 ->orWhere('precio_venta', '>=', $request->precio_minimo);
                        });
                    }

                    if ($request->has('precio_maximo')) {
                        $q->where(function ($subQ) use ($request) {
                            $subQ->where('precio_arriendo', '<=', $request->precio_maximo)
                                 ->orWhere('precio_venta', '<=', $request->precio_maximo);
                        });
                    }
                });
            }
        }

        if ($request->has('habitaciones')) {
            $habitaciones = is_array($request->habitaciones)
                ? $request->habitaciones
                : explode(',', $request->habitaciones);

            $query->whereIn('habitaciones', $habitaciones);
        }

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
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detalle' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'ciudad_id' => 'required|exists:ciudades,id',
            'direccion_completa' => 'nullable|string|max:500',
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

        $geocodingService = new GeocodingService();
        $coordinates = null;

        if ($request->direccion_completa) {
            $coordinates = $geocodingService->geocodeAddress($request->direccion_completa);
        }

        if (!$coordinates && $request->ciudad_id) {
            $coordinates = $geocodingService->getCoordinatesByCity($request->ciudad_id);
        }

        $propiedad = Propiedad::create([
            'detalle' => $request->detalle,
            'descripcion' => $request->descripcion,
            'ciudad_id' => $request->ciudad_id,
            'direccion_completa' => $request->direccion_completa,
            'habitaciones' => $request->habitaciones,
            'banios' => $request->banios,
            'tipo_transaccion' => $request->tipo_transaccion,
            'precio_arriendo' => $request->tipo_transaccion === 'arriendo' ? $request->precio_arriendo : null,
            'precio_venta' => $request->tipo_transaccion === 'venta' ? $request->precio_venta : null,
            'latitud' => $coordinates['lat'] ?? null,
            'longitud' => $coordinates['lng'] ?? null,
            'user_id' => auth()->id(),
        ]);

        if ($request->has('caracteristicas')) {
            $propiedad->caracteristicas()->sync($request->caracteristicas);
        }

        if ($request->hasFile('imagenes')) {
            $this->guardarImagenes($propiedad, $request->file('imagenes'));
        }

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
            'direccion_completa' => 'nullable|string|max:500',
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

        $needsGeocoding = $request->has('direccion_completa') || $request->has('ciudad_id');
        $coordinates = null;

        if ($needsGeocoding) {
            $geocodingService = new GeocodingService();

            if ($request->has('direccion_completa')) {
                $coordinates = $geocodingService->geocodeAddress($request->direccion_completa);
            }

            if (!$coordinates && $request->has('ciudad_id')) {
                $coordinates = $geocodingService->getCoordinatesByCity($request->ciudad_id);
            }
        }

        $data = $request->only([
            'detalle', 'descripcion', 'ciudad_id', 'direccion_completa',
            'habitaciones', 'banios', 'tipo_transaccion'
        ]);

        if ($request->has('tipo_transaccion')) {
            $data['precio_arriendo'] = $request->tipo_transaccion === 'arriendo' ? $request->precio_arriendo : null;
            $data['precio_venta'] = $request->tipo_transaccion === 'venta' ? $request->precio_venta : null;
        }

        if ($coordinates) {
            $data['latitud'] = $coordinates['lat'];
            $data['longitud'] = $coordinates['lng'];
        }

        $propiedad->update($data);

        if ($request->has('caracteristicas')) {
            $propiedad->caracteristicas()->sync($request->caracteristicas);
        }

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
        return response()->json(['message' => 'Propiedad eliminada correctamente']);
    }

    private function guardarImagenes(Propiedad $propiedad, array $imagenes)
    {
        $propiedad->load('imagenes');
        $tieneImagenPrincipal = $propiedad->imagenes->where('principal', true)->isNotEmpty();

        foreach ($imagenes as $index => $imagen) {
            if (!$imagen->isValid()) {
                continue;
            }

            $nombreArchivo = time() . '_' . uniqid() . '.' . $imagen->getClientOriginalExtension();
            $ruta = $imagen->storeAs('', $nombreArchivo, 'propiedades');

            $esPrincipal = !$tieneImagenPrincipal && $index === 0;

            PropiedadImagen::create([
                'propiedad_id' => $propiedad->id,
                'ruta_imagen' => $ruta,
                'principal' => $esPrincipal
            ]);

            if ($esPrincipal) {
                $tieneImagenPrincipal = true;
            }
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

    public function eliminarImagen(Request $request, Propiedad $propiedad, $imagenId)
    {
        if ($propiedad->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $imagen = $propiedad->imagenes()->findOrFail($imagenId);
        Storage::disk('propiedades')->delete($imagen->ruta_imagen);
        $imagen->delete();

        return response()->json(['message' => 'Imagen eliminada correctamente']);
    }
}
