<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ([
            ['name' => 'Rings', 'slug' => 'rings', 'sort_order' => 10],
            ['name' => 'Earings', 'slug' => 'earings', 'sort_order' => 20],
            ['name' => 'Necklace and Pendants', 'slug' => 'necklace-and-pendants', 'sort_order' => 30],
            ['name' => 'Bracelets', 'slug' => 'bracelets', 'sort_order' => 40],
        ] as $category) {
            DB::table('categories')->updateOrInsert(
                ['slug' => $category['slug']],
                [...$category, 'is_active' => true, 'updated_at' => $now, 'created_at' => $now],
            );
        }
    }

    public function down(): void
    {
        DB::table('categories')
            ->whereIn('slug', ['rings', 'earings', 'necklace-and-pendants', 'bracelets'])
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('products')
                    ->whereColumn('products.category_id', 'categories.id');
            })
            ->delete();
    }
};
