<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OverviewController;
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
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
    ]);
});

Route::get('/overview/kpis', [OverviewController::class, 'kpis']);
Route::get('/overview/revenue-trend', [OverviewController::class, 'revenueTrend']);
Route::get('/overview/low-stock', [OverviewController::class, 'lowStock']);
Route::get('/overview/recent-sales', [OverviewController::class, 'recentSales']);