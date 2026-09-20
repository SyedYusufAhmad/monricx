<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL may use the existing compound unique indexes to support these
        // foreign keys. Add dedicated indexes in separate ALTER statements
        // before replacing the unique indexes with variant-aware versions.
        Schema::table('cart_items', function (Blueprint $table): void {
            $table->index('cart_id', 'cart_items_cart_id_foreign_support');
        });

        Schema::table('cart_items', function (Blueprint $table): void {
            $table->dropUnique(['cart_id', 'product_id']);
            $table->foreignId('product_variant_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_variants')
                ->nullOnDelete();
            $table->unique(['cart_id', 'product_id', 'product_variant_id'], 'cart_product_variant_unique');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->foreignId('product_variant_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_variants')
                ->nullOnDelete();
        });

        Schema::table('stock_reservations', function (Blueprint $table): void {
            $table->index('order_id', 'stock_reservations_order_id_foreign_support');
        });

        Schema::table('stock_reservations', function (Blueprint $table): void {
            $table->dropUnique(['order_id', 'product_id']);
            $table->foreignId('product_variant_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_variants')
                ->nullOnDelete();
            $table->unique(
                ['order_id', 'product_id', 'product_variant_id'],
                'reservation_order_product_variant_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('stock_reservations', function (Blueprint $table): void {
            $table->dropUnique('reservation_order_product_variant_unique');
            $table->dropConstrainedForeignId('product_variant_id');
            $table->unique(['order_id', 'product_id']);
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('product_variant_id');
        });

        Schema::table('cart_items', function (Blueprint $table): void {
            $table->dropUnique('cart_product_variant_unique');
            $table->dropConstrainedForeignId('product_variant_id');
            $table->unique(['cart_id', 'product_id']);
        });

        Schema::table('stock_reservations', function (Blueprint $table): void {
            $table->dropIndex('stock_reservations_order_id_foreign_support');
        });

        Schema::table('cart_items', function (Blueprint $table): void {
            $table->dropIndex('cart_items_cart_id_foreign_support');
        });
    }
};
