<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Caracteristica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CaracteristicaController extends Controller
{
    public function index()
    {
        $caracteristicas = Caracteristica::orderBy('nombre')->get();
        return response()->json(['data' => $caracteristicas]);
    }

    public function show(Caracteristica $caracteristica)
    {
        return response()->json(['data' => $caracteristica]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255|unique:caracteristicas,nombre',
            'icono' => 'nullable|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $caracteristica = Caracteristica::create($validator->validated());
        return response()->json(['data' => $caracteristica], 201);
    }

    public function update(Request $request, Caracteristica $caracteristica)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255|unique:caracteristicas,nombre,' . $caracteristica->id,
            'icono' => 'nullable|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $caracteristica->update($validator->validated());
        return response()->json(['data' => $caracteristica]);
    }

    public function destroy(Caracteristica $caracteristica)
    {
        // Verificar si la característica tiene propiedades asociadas
        if ($caracteristica->propiedades()->count() > 0) {
            return response()->json([
                'error' => 'No se puede eliminar la característica porque está asociada a propiedades'
            ], 422);
        }

        $caracteristica->delete();
        return response()->json(['message' => 'Característica eliminada correctamente']);
    }

    // Opcional: Método para listar propiedades con una característica
    public function propiedades(Caracteristica $caracteristica)
    {
        $propiedades = $caracteristica->propiedades()->with(['ciudad', 'caracteristicas', 'imagenes'])->get();
        return response()->json(['data' => $propiedades]);
    }
}
