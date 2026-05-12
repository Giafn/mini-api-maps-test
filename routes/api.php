<?php

use App\Http\Controllers\PmtilesController;
use App\Http\Controllers\PointsController;
use Illuminate\Support\Facades\Route;

Route::post('pmtiles/import', [PmtilesController::class, 'import']);
Route::get('pmtiles', [PmtilesController::class, 'index']);
Route::get('pmtiles/{tileset}', [PmtilesController::class, 'show']);

Route::post('points', [PointsController::class, 'store']);
Route::get('points', [PointsController::class, 'index']);
