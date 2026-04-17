<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('assigned_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('related_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('related_serial_id')->nullable()->constrained('product_serials')->nullOnDelete();
            $table->string('device_brand', 100)->nullable();
            $table->string('device_model', 100)->nullable();
            $table->string('device_serial_number', 150)->nullable();
            $table->text('issue_description');
            $table->text('diagnosis_notes')->nullable();
            $table->enum('status', ['received', 'diagnosing', 'waiting_approval', 'in_progress', 'completed', 'delivered', 'cancelled'])->default('received');
            $table->decimal('labor_cost', 12, 2)->default(0);
            $table->decimal('parts_cost', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->dateTime('received_at');
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['assigned_employee_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_tickets');
    }
};
