<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PropiedadController;
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

// Rutas públicas de autenticación
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas públicas de propiedades
Route::get('/propiedades', [PropiedadController::class, 'index']);
Route::get('/propiedades/{propiedad}', [PropiedadController::class, 'show']);

// Rutas protegidas con autenticación
Route::middleware('auth:api')->group(function () {
    // Rutas de autenticación
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Rutas de gestión de propiedades
    Route::post('/propiedades', [PropiedadController::class, 'store']);
    Route::put('/propiedades/{propiedad}', [PropiedadController::class, 'update']);
    Route::delete('/propiedades/{propiedad}', [PropiedadController::class, 'destroy']);
});
