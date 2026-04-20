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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();

            # Foreign key referencing supplier_id in suppliers table
            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->cascadeOnDelete()
                ->restrictOnDelete(); 
            
            $table->string('po_number')->unique();
            $table->enum('status', ['draft', 'issued', 'transit','fulfilled', 'cancelled'])->default('draft');
            
            $table->date('ordered_at')->default(now());
            $table->date('expected_at')->nullable();
            $table->date('received_at')->nullable();

            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('shipping_cost', 12, 2)->default(0.00);
            $table->decimal('other_cost', 12, 2)->default(0.00);
            $table->decimal('total_cost', 12, 2)->default(0.00);

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
