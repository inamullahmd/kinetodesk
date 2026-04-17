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
        Schema::create('service_ticket_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_ticket_id')->constrained('service_tickets')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('product_serials')->nullOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_ticket_parts');
    }
};
