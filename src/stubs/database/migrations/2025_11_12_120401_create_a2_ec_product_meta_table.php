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
        Schema::create('a2_ec_product_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('a2_ec_products')->onDelete('cascade');
            $table->string('key');
            $table->longText('value')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a2_ec_product_meta');
    }
};

