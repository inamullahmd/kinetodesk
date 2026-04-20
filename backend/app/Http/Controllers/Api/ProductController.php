<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));

        $products = Product::query()
            ->with([
                'category:id,name',
                'brand:id,name',
                'preferredSupplier:id,name',
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('category_id', (int) $request->integer('category_id'));
            })
            ->when($request->filled('brand_id'), function ($query) use ($request) {
                $query->where('brand_id', (int) $request->integer('brand_id'));
            })
            ->when($request->filled('supplier_id'), function ($query) use ($request) {
                $query->where('preferred_supplier_id', (int) $request->integer('supplier_id'));
            })
            ->when($request->boolean('low_stock_only'), function ($query) {
                $query->where('product_type', '!=', 'service')
                    ->whereColumn('current_stock', '<=', 'reorder_threshold');
            })
            ->when($request->has('serialized_only'), function ($query) use ($request) {
                $query->where('is_serialized', $request->boolean('serialized_only'));
            })
            ->when($request->has('active_only'), function ($query) use ($request) {
                $query->where('is_active', $request->boolean('active_only'));
            })
            ->when($request->filled('product_type'), function ($query) use ($request) {
                $query->where('product_type', (string) $request->string('product_type'));
            })
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($products);
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::query()
            ->with([
                'category:id,name',
                'brand:id,name',
                'preferredSupplier:id,name',
                'supplierPrices' => function ($query) {
                    $query->with('supplier:id,name')
                        ->orderByDesc('effective_from');
                },
            ])
            ->findOrFail($id);

        return response()->json($product);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();

        $product = Product::create([
            'sku' => $data['sku'],
            'name' => $data['name'],
            'category_id' => $data['category_id'],
            'brand_id' => $data['brand_id'] ?? null,
            'product_type' => $data['product_type'],
            'is_serialized' => $data['is_serialized'],
            'unit_of_measure' => $data['unit_of_measure'] ?? 'pcs',
            'reorder_threshold' => $data['reorder_threshold'] ?? 0,
            'preferred_supplier_id' => $data['preferred_supplier_id'] ?? null,
            'current_stock' => $data['current_stock'] ?? 0,
            'reserved_stock' => $data['reserved_stock'] ?? 0,
            'sell_price' => $data['sell_price'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
            'description' => $data['description'] ?? null,
        ]);

        $product->load([
            'category:id,name',
            'brand:id,name',
            'preferredSupplier:id,name',
        ]);

        return response()->json([
            'message' => 'Product created successfully.',
            'product' => $product,
        ], 201);
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $product->update($request->validated());

        $product->load([
            'category:id,name',
            'brand:id,name',
            'preferredSupplier:id,name',
        ]);

        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => $product,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        return response()->json([
            'message' => 'Delete is disabled for now',
            'product_id' => $id,
        ], 405);
    }
}