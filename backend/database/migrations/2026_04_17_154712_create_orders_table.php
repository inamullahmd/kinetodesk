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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->enum('order_type', ['retail', 'b2b', 'custom_build']);
            $table->enum('channel', ['in_store', 'online', 'phone', 'walk_in'])->default('in_store');
            $table->enum('status', ['draft', 'pending_approval', 'confirmed', 'paid', 'partially_paid', 'cancelled', 'refunded', 'completed'])->default('draft');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->dateTime('created_at');
            $table->timestamp('updated_at')->nullable();

            $table->index(['customer_id', 'created_at']);
            $table->index(['employee_id', 'created_at']);
            $table->index(['order_type', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
