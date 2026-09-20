<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('source_id')->nullable()->unique()->after('id');
            $table->boolean('description_is_html')->default(false)->after('description');
        });

        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('source_id')->nullable()->unique();
            $table->string('title');
            $table->string('sku')->nullable();
            $table->unsignedBigInteger('price_paise');
            $table->unsignedBigInteger('discounted_price_paise')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');

        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique(['source_id']);
            $table->dropColumn(['source_id', 'description_is_html']);
        });
    }
};
