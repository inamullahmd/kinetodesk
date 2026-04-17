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
        Schema::create('build_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('build_template_id')->constrained('build_templates')->cascadeOnDelete();
            $table->string('component_type', 100);
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->integer('quantity')->default(1);
            $table->boolean('is_required')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('build_template_items');
    }
};
