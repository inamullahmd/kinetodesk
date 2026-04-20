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
        Schema::create('return_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('return_id')
                ->constrained('returns')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('sales_order_item_id')
                ->constrained('sales_order_items')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unsignedInteger('qty');

            $table->enum('item_condition', [
                'sealed',
                'opened',
                'used',
                'damaged',
            ])->default('opened');

            $table->enum('action', [
                'restock',
                'scrap',
            ])->default('restock');

            $table->decimal('refund_amount', 12, 2)->default(0.00);
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_items');
    }
};
