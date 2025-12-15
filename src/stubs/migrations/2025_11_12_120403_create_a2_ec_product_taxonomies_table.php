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
        Schema::create('a2_ec_product_taxonomies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('a2_ec_products')->onDelete('cascade');
            $table->foreignId('taxonomy_id')->constrained(config('vormia.table_prefix') . 'taxonomies')->onDelete('cascade');
            $table->string('type', 50);
            $table->timestamps();

            $table->unique(['product_id', 'taxonomy_id', 'type']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a2_ec_product_taxonomies');
    }
};
