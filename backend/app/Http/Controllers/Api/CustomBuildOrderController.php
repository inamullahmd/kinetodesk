<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomBuildOrderRequest;
use App\Models\CustomBuildOrder;
use App\Services\CustomBuildService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomBuildOrderController extends Controller
{
    private CustomBuildService $customBuildService;

    public function __construct(CustomBuildService $customBuildService)
    {
        $this->customBuildService = $customBuildService;
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));

        $builds = CustomBuildOrder::query()
            ->with([
                'order:id,order_number,customer_id,total_amount,status',
                'order.customer:id,business_name,first_name,last_name',
                'buildTemplate:id,name',
                'items.product:id,sku,name',
            ])
            ->when($request->filled('build_status'), function ($query) use ($request) {
                $query->where('build_status', (string) $request->string('build_status'));
            })
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($builds);
    }

    public function show(int $id): JsonResponse
    {
        $build = CustomBuildOrder::query()
            ->with([
                'order',
                'order.customer',
                'buildTemplate:id,name,description',
                'items.product:id,sku,name',
                'items.serial:id,serial_number',
            ])
            ->findOrFail($id);

        return response()->json($build);
    }

    public function store(StoreCustomBuildOrderRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $customBuild = $this->customBuildService->createCustomBuild(
                $data['customer_id'],
                $data['build_template_id'],
                $data['channel'],
                $data['items'],
                $data['labor_charge'] ?? null,
                $data['assembly_notes'] ?? null
            );

            $customBuild->load([
                'order:id,order_number,total_amount,status',
                'order.customer:id,business_name,first_name,last_name',
                'buildTemplate:id,name',
                'items.product:id,sku,name',
            ]);

            return response()->json([
                'message' => 'Custom build order created successfully.',
                'custom_build' => $customBuild,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating custom build: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function complete(int $id): JsonResponse
    {
        try {
            $build = CustomBuildOrder::with('items.product')->findOrFail($id);

            $this->customBuildService->completeBuild($build);

            $build->refresh()->load([
                'order:id,order_number,status',
                'buildTemplate:id,name',
                'items.product:id,sku,name',
            ]);

            return response()->json([
                'message' => 'Custom build completed successfully.',
                'custom_build' => $build,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error completing build: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function cancel(int $id): JsonResponse
    {
        try {
            $build = CustomBuildOrder::with('items.product')->findOrFail($id);

            $this->customBuildService->cancelBuild($build);

            $build->refresh();

            return response()->json([
                'message' => 'Custom build cancelled successfully.',
                'custom_build' => $build,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error cancelling build: ' . $e->getMessage(),
            ], 422);
        }
    }
}
