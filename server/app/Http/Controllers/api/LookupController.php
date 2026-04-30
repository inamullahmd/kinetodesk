<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity' => ['required', 'string', 'in:sales-orders,purchase-orders,customers,inventory'],
            'q' => ['required', 'string', 'min:1', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $entity = $validated['entity'];
        $query = trim($validated['q']);
        $limit = (int) ($validated['limit'] ?? 12);

        $results = match ($entity) {
            'sales-orders' => $this->salesOrders($query, $limit),
            'purchase-orders' => $this->purchaseOrders($query, $limit),
            'customers' => $this->customers($query, $limit),
            'inventory' => $this->inventory($query, $limit),
        };

        return response()->json([
            'entity' => $entity,
            'query' => $query,
            'results' => $results,
        ]);
    }

    private function salesOrders(string $query, int $limit): array
    {
        $like = '%' . $query . '%';
        $startsWith = $query . '%';

        return SalesOrder::query()
            ->with(['customer:id,customer_type,first_name,last_name,business_name,email,phone'])
            ->withCount('items')
            ->where(function ($salesOrderQuery) use ($like) {
                $salesOrderQuery
                    ->where('so_number', 'like', $like)
                    ->orWhere('contact_name', 'like', $like)
                    ->orWhere('contact_email', 'like', $like)
                    ->orWhere('contact_phone', 'like', $like)
                    ->orWhereHas('customer', function ($customerQuery) use ($like) {
                        $customerQuery
                            ->where('business_name', 'like', $like)
                            ->orWhere('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('phone', 'like', $like);
                    });
            })
            ->orderByRaw(
                'CASE WHEN so_number = ? THEN 0 WHEN so_number LIKE ? THEN 1 ELSE 2 END',
                [$query, $startsWith]
            )
            ->latest('ordered_at')
            ->limit($limit)
            ->get()
            ->map(function (SalesOrder $order) {
                $customerName = $order->customer
                    ? $this->customerName($order->customer)
                    : ($order->contact_name ?: 'Walk-in customer');

                return [
                    'entity' => 'sales-orders',
                    'type' => 'sales',
                    'id' => $order->id,
                    'label' => $order->so_number,
                    'title' => $order->so_number,
                    'subtitle' => $customerName,
                    'status' => $order->status,
                    'amount' => (float) $order->grand_total,
                    'date' => optional($order->ordered_at)->toDateString(),
                    'meta' => trim(($order->items_count ?? 0) . ' items · ' . ucfirst((string) $order->payment_status)),
                    'detailEndpoint' => "/api/orders/sales/{$order->id}",
                ];
            })
            ->values()
            ->all();
    }

    private function purchaseOrders(string $query, int $limit): array
    {
        $like = '%' . $query . '%';
        $startsWith = $query . '%';

        return PurchaseOrder::query()
            ->with(['supplier:id,name,email,phone,contact_person'])
            ->withCount('items')
            ->where(function ($purchaseOrderQuery) use ($like) {
                $purchaseOrderQuery
                    ->where('po_number', 'like', $like)
                    ->orWhere('status', 'like', $like)
                    ->orWhereHas('supplier', function ($supplierQuery) use ($like) {
                        $supplierQuery
                            ->where('name', 'like', $like)
                            ->orWhere('contact_person', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('phone', 'like', $like);
                    });
            })
            ->orderByRaw(
                'CASE WHEN po_number = ? THEN 0 WHEN po_number LIKE ? THEN 1 ELSE 2 END',
                [$query, $startsWith]
            )
            ->latest('ordered_at')
            ->limit($limit)
            ->get()
            ->map(function (PurchaseOrder $order) {
                return [
                    'entity' => 'purchase-orders',
                    'type' => 'purchase',
                    'id' => $order->id,
                    'label' => $order->po_number,
                    'title' => $order->po_number,
                    'subtitle' => $order->supplier?->name ?? 'Unknown supplier',
                    'status' => $order->status,
                    'amount' => (float) $order->total_cost,
                    'date' => optional($order->ordered_at)->toDateString(),
                    'meta' => trim(($order->items_count ?? 0) . ' items'),
                    'detailEndpoint' => "/api/orders/purchase/{$order->id}",
                ];
            })
            ->values()
            ->all();
    }

    private function customers(string $query, int $limit): array
    {
        $like = '%' . $query . '%';

        return Customer::query()
            ->withCount('salesOrders')
            ->where(function ($customerQuery) use ($like) {
                $customerQuery
                    ->where('business_name', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('tax_number', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('state', 'like', $like);
            })
            ->orderByRaw("COALESCE(NULLIF(business_name, ''), CONCAT(first_name, ' ', last_name)) ASC")
            ->limit($limit)
            ->get()
            ->map(function (Customer $customer) {
                return [
                    'entity' => 'customers',
                    'type' => 'customer',
                    'id' => $customer->id,
                    'label' => $this->customerName($customer),
                    'title' => $this->customerName($customer),
                    'subtitle' => trim(implode(' · ', array_filter([$customer->email, $customer->phone]))),
                    'status' => $customer->is_active ? 'active' : 'inactive',
                    'amount' => null,
                    'date' => null,
                    'meta' => trim(($customer->sales_orders_count ?? 0) . ' orders'),
                    'detailEndpoint' => "/api/customers/{$customer->id}",
                ];
            })
            ->values()
            ->all();
    }

    private function inventory(string $query, int $limit): array
    {
        $like = '%' . $query . '%';

        return Product::query()
            ->with(['brand:id,name', 'category:id,name'])
            ->where(function ($productQuery) use ($like) {
                $productQuery
                    ->where('internal_sku', 'like', $like)
                    ->orWhere('model_number', 'like', $like)
                    ->orWhere('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('brand', fn ($brandQuery) => $brandQuery->where('name', 'like', $like))
                    ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', $like));
            })
            ->orderBy('title')
            ->limit($limit)
            ->get()
            ->map(function (Product $product) {
                return [
                    'entity' => 'inventory',
                    'type' => 'inventory',
                    'id' => $product->id,
                    'label' => $product->internal_sku,
                    'title' => $product->title,
                    'subtitle' => trim(implode(' · ', array_filter([$product->brand?->name, $product->model_number]))),
                    'status' => $product->is_active ? 'active' : 'inactive',
                    'amount' => null,
                    'date' => null,
                    'meta' => $product->category?->name,
                    'detailEndpoint' => "/api/inventory/products/{$product->id}",
                ];
            })
            ->values()
            ->all();
    }

    private function customerName(Customer $customer): string
    {
        if ($customer->customer_type === 'business' && $customer->business_name) {
            return $customer->business_name;
        }

        return trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: 'Unnamed customer';
    }
}