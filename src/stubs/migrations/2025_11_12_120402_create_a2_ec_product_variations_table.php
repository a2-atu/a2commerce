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
        Schema::create('a2_ec_product_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('a2_ec_products')->onDelete('cascade');
            $table->foreignId('taxonomy_id')->nullable()->constrained(config('vormia.table_prefix') . 'taxonomies')->nullOnDelete();
            $table->decimal('price', 12, 2);
            $table->string('sku', 100)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('groupno')->nullable();
            $table->timestamps();

            $table->index('product_id');
            $table->index('taxonomy_id');
            $table->index('sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a2_ec_product_variations');
    }
};

