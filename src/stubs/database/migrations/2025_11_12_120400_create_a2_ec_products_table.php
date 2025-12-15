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
        Schema::create('a2_ec_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 12, 2);
            $table->enum('product_type', ['physical', 'digital', 'service', 'auction']);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_auction')->default(false);
            $table->boolean('is_service')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('product_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a2_ec_products');
    }
};

