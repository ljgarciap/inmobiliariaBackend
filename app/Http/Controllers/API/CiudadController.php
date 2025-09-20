<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Ciudad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CiudadController extends Controller
{
    public function index()
    {
        $ciudades = Ciudad::orderBy('nombre')->get();
        return response()->json(['data' => $ciudades]);
    }

    public function show(Ciudad $ciudad)
    {
        return response()->json(['data' => $ciudad]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255|unique:ciudades,nombre',
            'pais' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $ciudad = Ciudad::create($validator->validated());
        return response()->json(['data' => $ciudad], 201);
    }

    public function update(Request $request, Ciudad $ciudad)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255|unique:ciudades,nombre,' . $ciudad->id,
            'pais' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $ciudad->update($validator->validated());
        return response()->json(['data' => $ciudad]);
    }

    public function destroy(Ciudad $ciudad)
    {
        // Verificar si la ciudad tiene propiedades asociadas
        if ($ciudad->propiedades()->count() > 0) {
            return response()->json([
                'error' => 'No se puede eliminar la ciudad porque tiene propiedades asociadas'
            ], 422);
        }

        $ciudad->delete();
        return response()->json(['message' => 'Ciudad eliminada correctamente']);
    }

    // Opcional: Método para listar propiedades de una ciudad
    public function propiedades(Ciudad $ciudad)
    {
        $propiedades = $ciudad->propiedades()->with(['ciudad', 'caracteristicas', 'imagenes'])->get();
        return response()->json(['data' => $propiedades]);
    }
}
