<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OverviewController;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
    ]);
});

Route::get('/overview/kpis', [OverviewController::class, 'kpis']);
Route::get('/overview/revenue-trend', [OverviewController::class, 'revenueTrend']);
Route::get('/overview/low-stock', [OverviewController::class, 'lowStock']);
Route::get('/overview/recent-sales', [OverviewController::class, 'recentSales']);
Route::get('/overview/order-status', [OverviewController::class, 'orderStatus']);
Route::get('/overview/top-products', [OverviewController::class, 'topProducts']);
Route::get('/overview/sales-by-category', [OverviewController::class, 'salesByCategory']);