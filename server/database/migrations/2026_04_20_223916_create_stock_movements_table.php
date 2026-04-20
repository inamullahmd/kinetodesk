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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            # Foreign key referencing product_id in products table
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->restrictOnDelete();
            
            # Foreign key referencing stock_batch_id in stock_batches table
            $table->foreignId('stock_batch_id')
                ->constrained('stock_batches')
                ->cascadeOnDelete()
                ->restrictOnDelete();
            
            $table->enum('movement_type', [
                'purchase_receive',
                'sale',
                'return_from_customer',
                'adjustment_in',
                'adjustment_out',
                'damaged',
            ]);

            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->integer('qty_change');
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('moved_at')->useCurrent();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
