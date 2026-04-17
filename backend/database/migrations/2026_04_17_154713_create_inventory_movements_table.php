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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('product_serials')->nullOnDelete();
            $table->enum('movement_type', [
                'purchase_receive',
                'sale',
                'sale_return',
                'service_use',
                'service_return',
                'build_reserve',
                'build_consume',
                'build_release',
                'manual_adjustment',
                'transfer_in',
                'transfer_out',
                'defective_mark'
            ]);
            $table->integer('quantity');
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('moved_at');
            $table->timestamps();

            $table->index(['product_id', 'moved_at']);
            $table->index(['serial_id', 'moved_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
