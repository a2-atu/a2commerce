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
        Schema::create('a2_ec_comparison_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comparison_session_id')->constrained('a2_ec_comparison_sessions')->onDelete('cascade');
            $table->foreignId('product_a')->constrained('a2_ec_products')->onDelete('cascade');
            $table->foreignId('product_b')->constrained('a2_ec_products')->onDelete('cascade');
            $table->enum('action', ['viewed', 'compared', 'removed', 'purchased']);
            $table->timestamps();

            $table->index('comparison_session_id');
            $table->index(['product_a', 'product_b']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a2_ec_comparison_log');
    }
};

