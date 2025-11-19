<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;

// Sin middleware en el constructor, lo agregamos en las rutas
Route::get('/reports/ventas', [ReportController::class, 'reporteVentas'])->middleware('auth:api');
Route::get('/reports/ordenes', [ReportController::class, 'reporteOrdenes'])->middleware('auth:api');
Route::get('/reports/dashboard', [ReportController::class, 'dashboard'])->middleware('auth:api');

Route::post('/login', function (Request $request) {
    return response()->json(['error' => 'Use auth service at :8001'], 401);
});
