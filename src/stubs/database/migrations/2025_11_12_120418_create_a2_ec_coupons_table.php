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
        Schema::create('a2_ec_coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->enum('type', ['fixed', 'percent']);
            $table->decimal('value', 12, 2);
            $table->timestamp('expiry_date')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->timestamps();

            $table->index('code');
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a2_ec_coupons');
    }
};

