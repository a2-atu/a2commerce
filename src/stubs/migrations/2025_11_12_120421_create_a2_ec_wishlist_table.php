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
        Schema::create('a2_ec_wishlist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_id', 100)->nullable();
            $table->foreignId('product_id')->constrained('a2_ec_products')->onDelete('cascade');
            $table->timestamps();

            $table->index('user_id');
            $table->index('session_id');
            $table->index('product_id');
            $table->unique(['user_id', 'product_id'], 'user_product_unique');
            $table->unique(['session_id', 'product_id'], 'session_product_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a2_ec_wishlist');
    }
};

