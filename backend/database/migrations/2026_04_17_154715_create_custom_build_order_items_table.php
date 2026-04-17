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
        Schema::create('custom_build_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_build_order_id')->constrained('custom_build_orders')->cascadeOnDelete();
            $table->string('component_type', 100);
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('product_serials')->nullOnDelete();
            $table->integer('quantity')->default(1);
            $table->dateTime('reserved_at')->nullable();
            $table->dateTime('consumed_at')->nullable();
            $table->decimal('unit_cost_at_build', 12, 2);
            $table->decimal('unit_price_at_build', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_build_order_items');
    }
};
