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
        Schema::create('a2_ec_comparison_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comparison_session_id')->constrained('a2_ec_comparison_sessions')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('a2_ec_products')->onDelete('cascade');
            $table->timestamps();

            $table->index('comparison_session_id');
            $table->index('product_id');
            $table->unique(['comparison_session_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a2_ec_comparison_items');
    }
};

