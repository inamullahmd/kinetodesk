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
        Schema::create('product_supplier_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('supplier_sku', 100)->nullable();
            $table->decimal('cost_price', 12, 2);
            $table->char('currency_code', 3)->default('USD');
            $table->integer('minimum_order_qty')->default(1);
            $table->dateTime('effective_from');
            $table->dateTime('effective_to')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(
    ['product_id', 'supplier_id', 'effective_from'],
    'psp_prod_supp_eff_from_idx'
);

$table->index(
    ['supplier_id', 'effective_from', 'effective_to'],
    'psp_supp_eff_range_idx'
);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_supplier_prices');
    }
};
