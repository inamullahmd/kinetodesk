<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomBuildOrderController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\ReturnRequestController;
use App\Http\Controllers\Api\ServiceTicketController;
use App\Http\Controllers\Api\SupplierController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('admin.only')->group(function () {
        // Dashboard & Analytics
        Route::get('/dashboard/overview', [DashboardController::class, 'overview']);
        Route::get('/analytics/profit-margin', [AnalyticsController::class, 'profitMargin']);

        // Products
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::patch('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);

        // Purchase Orders
        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index']);
        Route::get('/purchase-orders/{id}', [PurchaseOrderController::class, 'show']);
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store']);
        Route::post('/purchase-orders/{id}/receive', [PurchaseOrderController::class, 'receive']);

        // Sales Orders
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::post('/orders/{id}/fulfill', [OrderController::class, 'fulfill']);
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);

        // Custom Builds
        Route::get('/custom-builds', [CustomBuildOrderController::class, 'index']);
        Route::get('/custom-builds/{id}', [CustomBuildOrderController::class, 'show']);
        Route::post('/custom-builds', [CustomBuildOrderController::class, 'store']);
        Route::post('/custom-builds/{id}/complete', [CustomBuildOrderController::class, 'complete']);
        Route::post('/custom-builds/{id}/cancel', [CustomBuildOrderController::class, 'cancel']);

        // Returns
        Route::get('/returns', [ReturnRequestController::class, 'index']);
        Route::get('/returns/{id}', [ReturnRequestController::class, 'show']);
        Route::post('/returns', [ReturnRequestController::class, 'store']);
        Route::post('/returns/{id}/process', [ReturnRequestController::class, 'process']);

        // Service Tickets
        Route::get('/service-tickets', [ServiceTicketController::class, 'index']);
        Route::get('/service-tickets/{id}', [ServiceTicketController::class, 'show']);
        Route::post('/service-tickets', [ServiceTicketController::class, 'store']);
        Route::patch('/service-tickets/{id}', [ServiceTicketController::class, 'update']);

        // Suppliers
        Route::get('/suppliers', [SupplierController::class, 'index']);
        Route::get('/suppliers/{id}', [SupplierController::class, 'show']);
        Route::post('/suppliers', [SupplierController::class, 'store']);
        Route::patch('/suppliers/{id}', [SupplierController::class, 'update']);
        Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);

        // Customers
        Route::get('/customers', [CustomerController::class, 'index']);
        Route::get('/customers/{id}', [CustomerController::class, 'show']);
        Route::post('/customers', [CustomerController::class, 'store']);
        Route::patch('/customers/{id}', [CustomerController::class, 'update']);
        Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);
    });
});