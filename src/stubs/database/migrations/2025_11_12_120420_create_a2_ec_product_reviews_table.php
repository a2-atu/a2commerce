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
        Schema::create('a2_ec_product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('a2_ec_products')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('name', 255)->nullable();

            $table->integer('rating');
            $table->string('title', 255)->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index('product_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a2_ec_product_reviews');
    }
};
