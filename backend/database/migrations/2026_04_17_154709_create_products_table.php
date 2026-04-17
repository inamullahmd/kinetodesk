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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 100)->unique();
            $table->string('name', 200);
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->enum('product_type', ['inventory', 'service', 'custom_component', 'bundle'])->default('inventory');
            $table->boolean('is_serialized')->default(false);
            $table->string('unit_of_measure', 30)->default('pcs');
            $table->integer('reorder_threshold')->default(0);
            $table->foreignId('preferred_supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->integer('current_stock')->default(0);
            $table->integer('reserved_stock')->default(0);
            $table->decimal('sell_price', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('category_id');
            $table->index('preferred_supplier_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
