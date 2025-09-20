<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PropiedadController;
use App\Http\Controllers\API\CiudadController;
use App\Http\Controllers\API\CaracteristicaController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/propiedades', [PropiedadController::class, 'index']);
Route::get('/propiedades/{propiedad}', [PropiedadController::class, 'show']);
Route::get('/ciudades', [CiudadController::class, 'index']);
Route::get('/caracteristicas', [CaracteristicaController::class, 'index']);

// Rutas protegidas con autenticación
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::post('/ciudades', [CiudadController::class, 'store']);
    Route::put('/ciudades/{ciudad}', [CiudadController::class, 'update']);
    Route::delete('/ciudades/{ciudad}', [CiudadController::class, 'destroy']);

    Route::post('/caracteristicas', [CaracteristicaController::class, 'store']);
    Route::put('/caracteristicas/{caracteristica}', [CaracteristicaController::class, 'update']);
    Route::delete('/caracteristicas/{caracteristica}', [CaracteristicaController::class, 'destroy']);

    Route::post('/propiedades', [PropiedadController::class, 'store']);
    Route::put('/propiedades/{propiedad}', [PropiedadController::class, 'update']);
    Route::delete('/propiedades/{propiedad}', [PropiedadController::class, 'destroy']);
    Route::post('/propiedades/{propiedad}/imagenes', [PropiedadController::class, 'agregarImagenes']);
});
