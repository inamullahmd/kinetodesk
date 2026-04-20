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
        Schema::create('supplier_products', function (Blueprint $table) {
            $table->id();

            # Foreign key referencing supplier_id in suppliers table
            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->cascadeOnDelete()
                ->restrictOnDelete();

            # Foreign key referencing product_id in products table
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->restrictOnDelete();

            $table->string('supplier_sku')->nullable();
            $table->boolean('preferred_supplier')->default(false);
            $table->unsignedInteger('min_order_qty')->default(1);
            $table->unsignedInteger('lead_time_days')->default(0);
            $table->decimal('last_cost', 12, 2)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            # Adding index for faster lookups by supplier_id and product_id
            $table->index(['supplier_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_products');
    }
};
