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
        Schema::create('a2_ec_auctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('a2_ec_products')->onDelete('cascade');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->decimal('starting_price', 12, 2);
            $table->decimal('reserve_price', 12, 2)->nullable();
            $table->timestamps();

            $table->index('product_id');
            $table->index('start_time');
            $table->index('end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a2_ec_auctions');
    }
};

