<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));

        $customers = Customer::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where('business_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($request->filled('customer_type'), function ($query) use ($request) {
                $query->where('customer_type', (string) $request->string('customer_type'));
            })
            ->when($request->boolean('active_only'), function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('business_name')
            ->orderBy('first_name')
            ->paginate($perPage);

        return response()->json($customers);
    }

    public function show(int $id): JsonResponse
    {
        $customer = Customer::query()
            ->with([
                'orders:id,order_number,status,total_amount,created_at',
                'payments:id,amount,payment_date',
                'creditTransactions:id,amount,transaction_type,created_at',
            ])
            ->findOrFail($id);

        return response()->json($customer);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $data = $request->validated();

        $customer = Customer::create([
            'customer_type' => $data['customer_type'],
            'business_name' => $data['business_name'] ?? null,
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'],
            'tax_number' => $data['tax_number'] ?? null,
            'billing_address' => $data['billing_address'] ?? null,
            'shipping_address' => $data['shipping_address'] ?? null,
            'credit_limit' => $data['credit_limit'] ?? 0,
            'current_balance' => 0,
            'payment_terms_days' => $data['payment_terms_days'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Customer created successfully.',
            'customer' => $customer,
        ], 201);
    }

    public function update(int $id, UpdateCustomerRequest $request): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $data = $request->validated();

        $customer->update($data);

        return response()->json([
            'message' => 'Customer updated successfully.',
            'customer' => $customer,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        // Soft delete by deactivating
        $customer->update(['is_active' => false]);

        return response()->json([
            'message' => 'Customer deactivated successfully.',
        ]);
    }
}
