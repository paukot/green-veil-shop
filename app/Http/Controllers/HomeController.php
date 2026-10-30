<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\SliderItem;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $heroSlides = SliderItem::query()
            ->visible()
            ->get()
            ->map(fn (SliderItem $slide) => [
                'id' => $slide->id,
                'image' => $slide->image,
                'url' => $slide->url,
                'url_opens_in_new_tab' => $slide->url_opens_in_new_tab,
                'title' => $slide->title,
                'subtitle' => $slide->subtitle,
                'alt_text' => $slide->alt_text,
                'button_label' => $slide->button_label,
            ])
            ->values();

        $promotions = Promotion::query()
            ->active()
            ->get()
            ->filter(fn (Promotion $promo) => $promo->isCurrentlyActive())
            ->map(fn (Promotion $promo) => [
                'id' => $promo->id,
                'name' => $promo->name,
                'description' => $promo->description,
                'code' => $promo->code,
                'discount_type' => $promo->discount_type->value,
                'discount_value' => (float) $promo->discount_value,
                'minimum_order_amount' => $promo->minimum_order_amount !== null ? (float) $promo->minimum_order_amount : null,
            ])
            ->values();

        $featuredCategories = Category::query()
            ->active()
            ->whereNull('parent_id')
            ->take(6)
            ->get()
            ->map(fn (Category $cat) => [
                'id' => $cat->id,
                'slug' => $cat->slug,
                'name' => $cat->name,
                'description' => $cat->description,
                'image' => $cat->image,
            ])
            ->values();

        $featuredProducts = Product::query()
            ->active()
            ->featured()
            ->with(['category'])
            ->orderBy('sort_order')
            ->take(8)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'sku' => $product->sku,
                'slug' => $product->slug,
                'name' => $product->name,
                'short_description' => $product->short_description,
                'price' => (float) $product->price,
                'compare_at_price' => $product->compare_at_price !== null ? (float) $product->compare_at_price : null,
                'discount_percentage' => $product->discountPercentage(),
                'main_image' => Storage::disk('public')->exists($product->main_image) ? asset("storage/$product->main_image") : asset('images/placeholder.png'),
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'slug' => $product->category->slug,
                    'name' => $product->category->name,
                ] : null,
                'is_in_stock' => $product->isInStock(),
            ])
            ->values();

        return Inertia::render('Home', [
            'heroSlides' => $heroSlides,
            'promotions' => $promotions,
            'featuredCategories' => $featuredCategories,
            'featuredProducts' => $featuredProducts,
        ]);
    }
}
