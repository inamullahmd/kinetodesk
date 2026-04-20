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
        Schema::create('product_specs', function (Blueprint $table) {
            $table->id();

            # Foreign key referencing product_id in products table
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->restrictOnDelete();

            $table->string('spec_key');
            $table->string('spec_value');
            $table->timestamps();

            # Adding index for faster lookups by product_id and spec_key
            $table->index(['product_id', 'spec_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_specs');
    }
};
