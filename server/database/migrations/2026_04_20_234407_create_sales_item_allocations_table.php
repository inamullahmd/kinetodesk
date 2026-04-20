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
        Schema::create('sales_item_allocations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sales_order_item_id')
                ->constrained('sales_order_items')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('stock_batch_id')
                ->constrained('stock_batches')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unsignedInteger('qty_allocated');
            $table->decimal('unit_cost', 12, 2);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_item_allocations');
    }
};
