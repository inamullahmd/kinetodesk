<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FulfillOrderRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));

        $orders = Order::query()
            ->with([
                'customer:id,customer_type,business_name,first_name,last_name',
                'employee:id,first_name,last_name',
                'items.product:id,sku,name',
            ])
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', (string) $request->string('status'));
            })
            ->when($request->filled('customer_id'), function ($query) use ($request) {
                $query->where('customer_id', (int) $request->integer('customer_id'));
            })
            ->when($request->filled('order_type'), function ($query) use ($request) {
                $query->where('order_type', (string) $request->string('order_type'));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where('order_number', 'like', "%{$search}%");
            })
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($orders);
    }

    public function show(int $id): JsonResponse
    {
        $order = Order::query()
            ->with([
                'customer',
                'employee:id,first_name,last_name',
                'items' => function ($query) {
                    $query->with([
                        'product:id,sku,name',
                        'serial:id,serial_number,status',
                    ]);
                },
                'payments',
            ])
            ->findOrFail($id);

        return response()->json($order);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $order = $this->orderService->createOrder(
                $data['customer_id'],
                $data['order_type'],
                $data['channel'],
                $data['items'],
                $data['discount_amount'] ?? null,
                $data['notes'] ?? null
            );

            $order->load([
                'customer:id,customer_type,business_name,first_name,last_name',
                'employee:id,first_name,last_name',
                'items.product:id,sku,name',
            ]);

            return response()->json([
                'message' => 'Order created successfully.',
                'order' => $order,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating order: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function fulfill(int $id, FulfillOrderRequest $request): JsonResponse
    {
        try {
            $order = Order::with('items.product')->findOrFail($id);
            $data = $request->validated();

            $this->orderService->fulfillOrder($order, $data['status'], $data['tracking_info'] ?? null);

            $order->refresh()->load([
                'customer:id,customer_type,business_name,first_name,last_name',
                'items.product:id,sku,name',
            ]);

            return response()->json([
                'message' => "Order {$data['status']} successfully.",
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fulfilling order: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function cancel(int $id): JsonResponse
    {
        try {
            $order = Order::with('items.product')->findOrFail($id);

            $this->orderService->cancelOrder($order);

            $order->refresh();

            return response()->json([
                'message' => 'Order cancelled successfully.',
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error cancelling order: ' . $e->getMessage(),
            ], 422);
        }
    }
}
