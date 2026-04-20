<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));

        $suppliers = Supplier::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('supplier_code', 'like', "%{$search}%");
            })
            ->when($request->boolean('active_only'), function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($suppliers);
    }

    public function show(int $id): JsonResponse
    {
        $supplier = Supplier::query()
            ->with([
                'purchaseOrders:id,po_number,status,total_amount,created_at',
                'supplierPrices' => function ($query) {
                    $query->with('product:id,sku,name')->orderByDesc('effective_from')->limit(10);
                },
            ])
            ->findOrFail($id);

        return response()->json($supplier);
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $data = $request->validated();

        $supplier = Supplier::create([
            'supplier_code' => $data['supplier_code'],
            'name' => $data['name'],
            'contact_person' => $data['contact_person'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'default_lead_time_days' => $data['default_lead_time_days'] ?? 7,
            'rating' => $data['rating'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Supplier created successfully.',
            'supplier' => $supplier,
        ], 201);
    }

    public function update(int $id, UpdateSupplierRequest $request): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);
        $data = $request->validated();

        $supplier->update($data);

        return response()->json([
            'message' => 'Supplier updated successfully.',
            'supplier' => $supplier,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        // Soft delete by deactivating
        $supplier->update(['is_active' => false]);

        return response()->json([
            'message' => 'Supplier deactivated successfully.',
        ]);
    }
}
