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
        Schema::create('a2_ec_order_address', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('a2_ec_orders')->onDelete('cascade');
            $table->enum('type', ['billing', 'shipping']);

            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();

            $table->text('address_line');
            $table->string('city', 100);
            $table->string('country', 100);
            $table->string('postal_code', 50)->nullable();
            $table->timestamps();

            $table->index(['order_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a2_ec_order_address');
    }
};
