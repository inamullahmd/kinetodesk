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
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();

            # Foreign key referencing product_id in products table
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->restrictOnDelete();   

            # Foreign key referencing purchase_order_item_id in purchase_order_items table
            $table->foreignId('purchase_order_item_id')
                ->constrained('purchase_order_items')
                ->cascadeOnDelete()
                ->restrictOnDelete();

            
            $table->string('batch_code')->nullable()->unique();
            $table->unsignedInteger('qty_received');
            $table->unsignedInteger('qty_remaining');
            $table->decimal('unit_cost', 12, 2);
            $table->date('received_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
