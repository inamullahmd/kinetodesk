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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sales_order_id')
                ->constrained('sales_orders')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->enum('payment_method', [
                'cash',
                'card',
                'bank_transfer',
                'mobile_money',
                'other',
            ]);

            $table->decimal('amount', 12, 2);
            $table->enum('status', [
                'paid',
                'refunded',
            ])->default('paid');

            $table->string('transaction_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();

            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
