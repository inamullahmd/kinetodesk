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
        Schema::create('serial_numbers', function (Blueprint $table) {
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

            $table->string('serial_number')->unique();

            $table->enum('status', [
                'available',
                'sold',
                'returned',
                'damaged',
            ])->default('available');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serial_numbers');
    }
};
