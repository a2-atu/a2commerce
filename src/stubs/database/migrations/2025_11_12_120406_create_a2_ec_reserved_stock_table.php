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
        Schema::create('a2_ec_reserved_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('a2_ec_products')->onDelete('cascade');
            $table->foreignId('variation_id')->nullable()->constrained('a2_ec_product_variations')->nullOnDelete();
            $table->string('cart_id', 100);
            $table->unsignedInteger('quantity')->default(1);
            $table->boolean('in_checkout')->default(true);
            $table->timestamp('expire_at');
            $table->timestamps();

            $table->index(['product_id', 'variation_id']);
            $table->index('cart_id');
            $table->index('expire_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a2_ec_reserved_stock');
    }
};

