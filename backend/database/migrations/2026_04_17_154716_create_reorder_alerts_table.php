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
        Schema::create('reorder_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->integer('current_stock');
            $table->integer('reorder_threshold');
            $table->integer('suggested_quantity')->default(0);
            $table->enum('status', ['open', 'po_created', 'dismissed'])->default('open');
            $table->dateTime('detected_at');
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'detected_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reorder_alerts');
    }
};
