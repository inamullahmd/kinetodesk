<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceTicketRequest;
use App\Http\Requests\UpdateServiceTicketRequest;
use App\Models\ServiceTicket;
use App\Services\ServiceTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceTicketController extends Controller
{
    private ServiceTicketService $serviceTicketService;

    public function __construct(ServiceTicketService $serviceTicketService)
    {
        $this->serviceTicketService = $serviceTicketService;
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));

        $tickets = ServiceTicket::query()
            ->with([
                'customer:id,business_name,first_name,last_name,phone,email',
                'assignedEmployee:id,first_name,last_name',
                'relatedOrder:id,order_number',
                'parts.product:id,sku,name',
            ])
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', (string) $request->string('status'));
            })
            ->when($request->filled('customer_id'), function ($query) use ($request) {
                $query->where('customer_id', (int) $request->integer('customer_id'));
            })
            ->when($request->filled('assigned_employee_id'), function ($query) use ($request) {
                $query->where('assigned_employee_id', (int) $request->integer('assigned_employee_id'));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));

                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('ticket_number', 'like', "%{$search}%")
                        ->orWhere('device_serial_number', 'like', "%{$search}%")
                        ->orWhere('device_brand', 'like', "%{$search}%")
                        ->orWhere('device_model', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('received_at')
            ->paginate($perPage);

        return response()->json($tickets);
    }

    public function show(int $id): JsonResponse
    {
        $ticket = ServiceTicket::query()
            ->with([
                'customer',
                'assignedEmployee:id,first_name,last_name,email,phone',
                'relatedOrder:id,order_number',
                'relatedSerial:id,serial_number,product_id',
                'parts' => function ($query) {
                    $query->with('product:id,sku,name', 'serial:id,serial_number');
                },
            ])
            ->findOrFail($id);

        return response()->json($ticket);
    }

    public function store(StoreServiceTicketRequest $request): JsonResponse
    {
        try {
            $ticket = $this->serviceTicketService->createTicket($request->validated());

            return response()->json([
                'message' => 'Service ticket created successfully.',
                'service_ticket' => $ticket,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error creating service ticket: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function update(int $id, UpdateServiceTicketRequest $request): JsonResponse
    {
        try {
            $ticket = ServiceTicket::query()->findOrFail($id);
            $updatedTicket = $this->serviceTicketService->updateTicket($ticket, $request->validated());

            return response()->json([
                'message' => 'Service ticket updated successfully.',
                'service_ticket' => $updatedTicket,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error updating service ticket: ' . $e->getMessage(),
            ], 422);
        }
    }
}