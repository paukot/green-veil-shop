<?php

namespace App\Http\Controllers;

use App\Enums\CacheKey;
use App\Enums\CacheTag;
use App\Http\Resources\PromotionResource;
use App\Http\Resources\SimpleCategoryResource;
use App\Http\Resources\SimpleProductResource;
use App\Http\Resources\SliderItemResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\SliderItem;
use App\Services\Cache\AppCache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $heroSlides = AppCache::remember(
            CacheKey::HomeHeroSlides,
            fn () => SliderItemResource::collection(SliderItem::query()->visible()->get())->resolve(),
            [CacheTag::Home],
        );

        $promotions = AppCache::remember(
            CacheKey::HomePromotions,
            fn () => PromotionResource::collection(
                Promotion::query()
                    ->active()
                    ->get()
                    ->filter(fn (Promotion $promo) => $promo->isCurrentlyActive())
            )->resolve(),
            [CacheTag::Home, CacheTag::Promotions],
        );

        $featuredCategories = AppCache::remember(
            CacheKey::HomeFeaturedCategories,
            fn () => SimpleCategoryResource::collection(
                Category::query()
                    ->active()
                    ->whereNull('parent_id')
                    ->take(6)
                    ->get()
            )->resolve(),
            [CacheTag::Home, CacheTag::Categories],
        );

        return Inertia::render('Home', [
            'heroSlides' => $heroSlides,
            'promotions' => $promotions,
            'featuredCategories' => $featuredCategories,
            'featuredProducts' => Inertia::defer(fn () => AppCache::remember(
                CacheKey::HomeFeaturedProducts,
                fn () => SimpleProductResource::collection(
                    Product::query()
                        ->active()
                        ->featured()
                        ->with(['category'])
                        ->orderBy('sort_order')
                        ->take(8)
                        ->get()
                )->resolve(),
                [CacheTag::Home, CacheTag::Products],
            )),
        ]);
    }
}
