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
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 50)->unique();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->enum('status', ['initiated', 'approved', 'rejected', 'received', 'refunded', 'exchanged', 'replaced'])->default('initiated');
            $table->text('reason');
            $table->decimal('refund_amount', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('return_date')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'return_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('returns');
    }
};
