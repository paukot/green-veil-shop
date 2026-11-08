<?php

namespace App\Enums;

enum CacheKey: string
{
    // Countries
    case CountriesOptions = 'countries.options_list';
    case CountriesShipping = 'countries.shipping_list';

    // Shipping & Payments
    case DeliveryOptionsActive = 'shipping.delivery_options_active';
    case PaymentOptionsActive = 'settings.payment_options_active';

    // Navigation & Storefront Structure
    case StorefrontNavigation = 'storefront.category_navigation';

    // Home Page Aggregations
    case HomeHeroSlides = 'home.hero_slides';
    case HomePromotions = 'home.promotions';
    case HomeFeaturedCategories = 'home.featured_categories';
    case HomeFeaturedProducts = 'home.featured_products';

    // Facets & Catalog Aggregations
    case CatalogFacetsGlobal = 'catalog.facets.global';
    case CatalogFacetsCategoryPrefix = 'catalog.facets.category';
    case CatalogCategoriesList = 'catalog.categories_list';

    /**
     * Generate dynamic key with parameter.
     */
    public function with(int|string ...$params): string
    {
        return $this->value.'.'.implode('.', $params);
    }
}
