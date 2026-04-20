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
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();

            # Foreign key referencing customer_id in customers table
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete()
                ->restrictOnDelete();
            
            # Foreign key referencing employee_id in employees table (salesperson)
            $table->foreignId('employee_id')
                ->nullable()
                ->constrained('employees')
                ->cascadeOnDelete()
                ->restrictOnDelete();
            
            $table->string('so_number')->unique();

            $table->enum('channel', [
                'online',
                'in_store',
                'phone',
            ])->default('in_store');

            $table->enum('status', [
                'pending',
                'confirmed',
                'shipped',
                'delivered',
                'cancelled',
                'returned',
                'refunded'
            ])->default('pending');

            $table->enum('payment_status', [
                'unpaid',
                'paid',
                'refunded',
            ])->default('unpaid');

            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('shipping_fee', 12, 2)->default(0.00);
            $table->decimal('grand_total', 12, 2)->default(0.00);

            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('delivery_address_line_1')->nullable();
            $table->string('delivery_address_line_2')->nullable();
            $table->string('delivery_city')->nullable();
            $table->string('delivery_state')->nullable();
            $table->string('delivery_postal_code')->nullable();
            $table->string('delivery_country')->nullable();

            $table->text('notes')->nullable();

            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
