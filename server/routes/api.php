<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\DashboardController;
use App\Http\Controllers\api\OrdersController;
use App\Http\Controllers\api\InventoryController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/dashboard', DashboardController::class);

Route::get('/orders', OrdersController::class);
Route::get('/orders/{type}/{id}', [OrdersController::class, 'show']);


Route::get('/inventory/overview', [InventoryController::class, 'overview']);
Route::get('/inventory/products', [InventoryController::class, 'products']);
Route::get('/inventory/products/{id}', [InventoryController::class, 'productDetail']);
Route::get('/inventory/alerts', [InventoryController::class, 'alerts']);