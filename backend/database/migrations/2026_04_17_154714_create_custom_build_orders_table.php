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
        Schema::create('custom_build_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('build_template_id')->nullable()->constrained('build_templates')->nullOnDelete();
            $table->enum('build_status', ['draft', 'stock_check_pending', 'ready', 'assembling', 'completed', 'delivered', 'cancelled'])->default('draft');
            $table->text('assembly_notes')->nullable();
            $table->decimal('labor_charge', 12, 2)->default(0);
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_build_orders');
    }
};
