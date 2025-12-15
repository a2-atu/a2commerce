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
        Schema::create('a2_ec_inventory_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('a2_ec_products')->onDelete('cascade');
            $table->enum('event', ['add', 'remove', 'adjust']);
            $table->integer('quantity');
            $table->foreignId('actor_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index('product_id');
            $table->index('actor_id');
            $table->index('event');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a2_ec_inventory_events');
    }
};

