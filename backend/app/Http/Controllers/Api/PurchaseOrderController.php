<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReceivePurchaseOrderRequest;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Models\Employee;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Role;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    private InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));

        $purchaseOrders = PurchaseOrder::query()
            ->with([
                'supplier:id,name',
                'orderedByEmployee:id,first_name,last_name',
                'items' => function ($query) {
                    $query->with('product:id,sku,name,is_serialized');
                },
            ])
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', (string) $request->string('status'));
            })
            ->when($request->filled('supplier_id'), function ($query) use ($request) {
                $query->where('supplier_id', (int) $request->integer('supplier_id'));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where('po_number', 'like', "%{$search}%");
            })
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($purchaseOrders);
    }

    public function show(int $id): JsonResponse
    {
        $purchaseOrder = PurchaseOrder::query()
            ->with([
                'supplier:id,name,email,phone',
                'orderedByEmployee:id,first_name,last_name,email',
                'items' => function ($query) {
                    $query->with([
                        'product:id,sku,name,is_serialized',
                        'serials:id,purchase_order_item_id,serial_number,status,warranty_expiry_date',
                    ]);
                },
            ])
            ->findOrFail($id);

        return response()->json($purchaseOrder);
    }

    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $data = $request->validated();
                $employeeId = $this->resolveEmployeeIdForAuthenticatedUser();

                $poNumber = 'PO-' . now()->format('Ymd') . '-' . str_pad((string) (PurchaseOrder::count() + 1), 5, '0', STR_PAD_LEFT);

                $subtotal = 0.0;
                foreach ($data['items'] as $item) {
                    $subtotal += ((float) $item['unit_cost']) * ((int) $item['quantity_ordered']);
                }

                $purchaseOrder = PurchaseOrder::create([
                    'po_number' => $poNumber,
                    'supplier_id' => $data['supplier_id'],
                    'status' => 'sent',
                    'ordered_by_employee_id' => $employeeId,
                    'order_date' => now(),
                    'expected_date' => $data['expected_date'],
                    'received_at' => null,
                    'subtotal' => $subtotal,
                    'tax_amount' => 0,
                    'shipping_amount' => 0,
                    'total_amount' => $subtotal,
                    'notes' => $data['notes'] ?? null,
                ]);

                foreach ($data['items'] as $item) {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'product_id' => $item['product_id'],
                        'quantity_ordered' => (int) $item['quantity_ordered'],
                        'quantity_received' => 0,
                        'unit_cost' => (float) $item['unit_cost'],
                        'line_total' => ((float) $item['unit_cost']) * ((int) $item['quantity_ordered']),
                    ]);
                }

                $purchaseOrder->load([
                    'supplier:id,name',
                    'orderedByEmployee:id,first_name,last_name',
                    'items' => function ($query) {
                        $query->with('product:id,sku,name,is_serialized');
                    },
                ]);

                return response()->json([
                    'message' => 'Purchase order created successfully.',
                    'purchase_order' => $purchaseOrder,
                ], 201);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error creating purchase order: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function receive(int $id, ReceivePurchaseOrderRequest $request): JsonResponse
    {
        $purchaseOrder = PurchaseOrder::with('items.product', 'items.serials')->findOrFail($id);

        if ($purchaseOrder->status === 'received') {
            return response()->json([
                'message' => 'This purchase order has already been fully received.',
            ], 422);
        }

        if (! in_array($purchaseOrder->status, ['sent', 'partially_received'], true)) {
            return response()->json([
                'message' => 'Only sent or partially received purchase orders can be received.',
            ], 422);
        }

        $data = $request->validated();
        $receivedQuantities = $data['received_quantity'] ?? [];
        $serials = $data['serials'] ?? null;

        foreach ($purchaseOrder->items as $item) {
            $receivedQty = (int) ($receivedQuantities[$item->id] ?? 0);
            $outstandingQty = (int) $item->quantity_ordered - (int) $item->quantity_received;

            if ($receivedQty > $outstandingQty) {
                return response()->json([
                    'message' => "Product {$item->product->sku}: Received quantity ({$receivedQty}) exceeds outstanding quantity ({$outstandingQty}).",
                ], 422);
            }

            if ($item->product->is_serialized) {
                $providedSerials = $serials[$item->id] ?? [];

                if ($receivedQty > 0 && count($providedSerials) !== $receivedQty) {
                    return response()->json([
                        'message' => "Product {$item->product->sku}: Serial count must match received quantity.",
                    ], 422);
                }
            }
        }

        try {
            $this->inventoryService->receivePurchaseOrder(
                $purchaseOrder,
                $receivedQuantities,
                $serials
            );

            $purchaseOrder->refresh()->load([
                'supplier:id,name',
                'orderedByEmployee:id,first_name,last_name',
                'items' => function ($query) {
                    $query->with([
                        'product:id,sku,name,is_serialized',
                        'serials:id,purchase_order_item_id,serial_number,status,warranty_expiry_date',
                    ]);
                },
            ]);

            return response()->json([
                'message' => 'Purchase order received successfully.',
                'purchase_order' => $purchaseOrder,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error receiving purchase order: ' . $e->getMessage(),
            ], 422);
        }
    }

    private function resolveEmployeeIdForAuthenticatedUser(): int
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user) {
            throw new \Exception('Authenticated user not found.');
        }

        if ($user->employee_id) {
            return (int) $user->employee_id;
        }

        $employeeEmail = 'emp-' . $user->id . '@kinetodesk.local';

        $employee = Employee::query()->where('email', $employeeEmail)->first();

        if (! $employee) {
            $adminRole = Role::query()->firstOrCreate(
                ['code' => 'admin'],
                ['name' => 'Administrator']
            );

            $employee = Employee::create([
                'employee_code' => 'EMP-' . str_pad((string) $user->id, 5, '0', STR_PAD_LEFT),
                'email' => $employeeEmail,
                'first_name' => explode(' ', $user->name)[0] ?? 'User',
                'last_name' => explode(' ', $user->name)[1] ?? 'Unknown',
                'role_id' => $adminRole->id,
                'password_hash' => bcrypt('password'),
                'is_active' => true,
            ]);
        }

        $userModel = User::query()->find($user->id);

        if (! $userModel) {
            throw new \Exception('Unable to persist employee linkage for authenticated user.');
        }

        $userModel->update(['employee_id' => $employee->id]);

        return (int) $employee->id;
    }
}