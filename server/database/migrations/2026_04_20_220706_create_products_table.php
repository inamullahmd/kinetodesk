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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            # Foreign key referencing category_id in categories table
            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnDelete()
                ->restrictOnDelete();
            
            # Foreign key referencing brand_id in brands table
            $table->foreignId('brand_id')
                ->constrained('brands')
                ->cascadeOnDelete()
                ->restrictOnDelete();
            
            $table->string('internal_sku')->unique();
            $table->string('model_number')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('commission_value', 12, 2)->default(10.00);
            $table->boolean('is_serialized')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
