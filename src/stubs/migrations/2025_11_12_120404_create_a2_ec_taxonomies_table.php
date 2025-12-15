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
        Schema::create('a2_ec_taxonomies', function (Blueprint $table) {
            $table->id();
            $table->string('type', 100);
            $table->string('group', 100);
            $table->string('for', 100);
            $table->foreignId('taxonomy_id')->nullable()->constrained(config('vormia.table_prefix') . 'taxonomies')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'group', 'for']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a2_ec_taxonomies');
    }
};

