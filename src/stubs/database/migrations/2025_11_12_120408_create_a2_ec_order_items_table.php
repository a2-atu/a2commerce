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
        Schema::create('a2_ec_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('a2_ec_orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('a2_ec_products')->onDelete('cascade');
            $table->foreignId('variation_id')->nullable()->constrained('a2_ec_product_variations')->nullOnDelete();
            $table->decimal('price', 12, 2);
            $table->integer('quantity');
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a2_ec_order_items');
    }
};

