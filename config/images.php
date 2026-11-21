<?php

use App\Models\AdminUser;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\SliderItem;

return [

    /*
    |--------------------------------------------------------------------------
    | Image processing
    |--------------------------------------------------------------------------
    |
    | Uploaded raster images are downscaled to a capped "master" and given
    | compressed WebP variants at the widths of their preset. Variants live
    | beside the original: products/abc.jpg -> products/variants/abc-400.webp.
    |
    */

    'disk' => env('IMAGES_DISK', 'public'),

    /** Longest side of the stored original, in pixels. Larger uploads are scaled down. */
    'master_max' => 2000,

    'master_quality' => 82,

    'variant_quality' => 78,

    /** Only these extensions are processed; SVG and anything else is served as uploaded. */
    'extensions' => ['jpg', 'jpeg', 'png', 'webp'],

    /** Models using the HasImageVariants trait (for the backfill and shared-file checks). */
    'models' => [
        Product::class,
        ProductImage::class,
        ProductVariant::class,
        Category::class,
        SliderItem::class,
        Brand::class,
        AdminUser::class,
    ],

    /** Variant widths, in pixels, per kind of image. */
    'presets' => [
        'product' => [400, 800, 1200],
        'category' => [400, 800, 1200],
        'slider' => [800, 1280, 1920],
        'brand' => [200, 400],
        'avatar' => [128, 256],
    ],

];
