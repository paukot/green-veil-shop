<?php

namespace App\Enums;

enum CacheTag: string
{
    case Catalog = 'tag:catalog';
    case Products = 'tag:products';
    case Categories = 'tag:categories';
    case Taxonomies = 'tag:taxonomies';
    case Shipping = 'tag:shipping';
    case Settings = 'tag:settings';
    case Promotions = 'tag:promotions';
    case Home = 'tag:home';
}
