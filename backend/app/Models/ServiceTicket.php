<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'customer_id',
        'assigned_employee_id',
        'related_order_id',
        'related_serial_id',
        'device_brand',
        'device_model',
        'device_serial_number',
        'issue_description',
        'diagnosis_notes',
        'status',
        'labor_cost',
        'parts_cost',
        'total_cost',
        'received_at',
        'completed_at',
        'delivered_at',
    ];

    protected $casts = [
        'labor_cost' => 'decimal:2',
        'parts_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'received_at' => 'datetime',
        'completed_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }

    public function relatedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'related_order_id');
    }

    public function relatedSerial(): BelongsTo
    {
        return $this->belongsTo(ProductSerial::class, 'related_serial_id');
    }

    public function parts(): HasMany
    {
        return $this->hasMany(ServiceTicketPart::class);
    }
}