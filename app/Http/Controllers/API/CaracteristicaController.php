<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Caracteristica;
use Illuminate\Http\Request;

class CaracteristicaController extends Controller
{
    public function index()
    {
        $caracteristicas = Caracteristica::orderBy('nombre')->get();
        return response()->json(['data' => $caracteristicas]);
    }
}
