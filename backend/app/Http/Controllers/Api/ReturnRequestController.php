<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReturnRequestRequest;
use App\Models\ReturnRequest;
use App\Services\ReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReturnRequestController extends Controller
{
    private ReturnService $returnService;

    public function __construct(ReturnService $returnService)
    {
        $this->returnService = $returnService;
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));

        $returns = ReturnRequest::query()
            ->with([
                'order:id,order_number,total_amount',
                'customer:id,business_name,first_name,last_name',
                'items.orderItem.product:id,sku,name',
            ])
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', (string) $request->string('status'));
            })
            ->when($request->filled('customer_id'), function ($query) use ($request) {
                $query->where('customer_id', (int) $request->integer('customer_id'));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where('return_number', 'like', "%{$search}%");
            })
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($returns);
    }

    public function show(int $id): JsonResponse
    {
        $return = ReturnRequest::query()
            ->with([
                'order:id,order_number,total_amount',
                'order.items',
                'customer',
                'items.orderItem.product:id,sku,name,sell_price',
                'items.serial:id,serial_number',
            ])
            ->findOrFail($id);

        return response()->json($return);
    }

    public function store(StoreReturnRequestRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $return = $this->returnService->createReturn(
                $data['order_id'],
                $data['reason'],
                $data['items']
            );

            $return->load([
                'order:id,order_number,total_amount',
                'customer:id,business_name,first_name,last_name',
                'items.orderItem.product:id,sku,name',
            ]);

            return response()->json([
                'message' => 'Return request created successfully.',
                'return' => $return,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating return: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function process(int $id, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'status' => ['required', 'in:approved,rejected,refunded,exchanged'],
            ]);

            $return = ReturnRequest::with('items.orderItem.product')->findOrFail($id);

            $this->returnService->processReturn($return, $request->string('status'));

            $return->refresh()->load([
                'order:id,order_number',
                'items.orderItem.product:id,sku,name',
            ]);

            return response()->json([
                'message' => 'Return processed successfully.',
                'return' => $return,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error processing return: ' . $e->getMessage(),
            ], 422);
        }
    }
}
