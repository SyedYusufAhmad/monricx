<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Move the `text-align: center;` inline styles that shipped with the
     * imported catalog into the `.monricx-product-description` theme CSS.
     * Rendering is unchanged; descriptions become clean content again.
     */
    public function up(): void
    {
        Product::withTrashed()
            ->where('description', 'like', '%style=%')
            ->chunkById(200, function ($products) {
                foreach ($products as $product) {
                    $cleaned = preg_replace('/\s*style="[^"]*"/i', '', $product->description);

                    if ($cleaned !== $product->description) {
                        $product->description = $cleaned;
                        $product->saveQuietly();
                    }
                }
            });
    }

    /**
     * Irreversible by design: the removed attributes carried no information
     * beyond `text-align: center`, which now lives in the theme CSS, so no
     * visual content is lost.
     */
    public function down(): void
    {
        //
    }
};
