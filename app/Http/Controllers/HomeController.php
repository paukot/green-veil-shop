<?php

namespace App\Http\Controllers;

use App\Http\Resources\PromotionResource;
use App\Http\Resources\SimpleCategoryResource;
use App\Http\Resources\SimpleProductResource;
use App\Http\Resources\SliderItemResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\SliderItem;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $heroSlides = SliderItem::query()->visible()->get();

        $promotions = Promotion::query()
            ->active()
            ->get()
            ->filter(fn (Promotion $promo) => $promo->isCurrentlyActive());

        $featuredCategories = Category::query()
            ->active()
            ->whereNull('parent_id')
            ->take(6)
            ->get();

        $featuredProducts = Product::query()
            ->active()
            ->featured()
            ->with(['category'])
            ->orderBy('sort_order')
            ->take(8)
            ->get();

        return Inertia::render('Home', [
            'heroSlides' => SliderItemResource::collection($heroSlides)->resolve(),
            'promotions' => PromotionResource::collection($promotions)->resolve(),
            'featuredCategories' => SimpleCategoryResource::collection($featuredCategories)->resolve(),
            'featuredProducts' => SimpleProductResource::collection($featuredProducts)->resolve(),
        ]);
    }
}
