<script setup lang="ts">
import { Head, Link, WhenVisible } from '@inertiajs/vue3';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import HeroSlider from '@/components/storefront/HeroSlider.vue';
import PromoBanner from '@/components/storefront/PromoBanner.vue';
import ProductGrid from '@/components/storefront/ProductGrid.vue';
import ProductGridSkeleton from '@/components/storefront/skeletons/ProductGridSkeleton.vue';
import CategoryCard from '@/components/storefront/CategoryCard.vue';
import type { HomePageProps } from '@/types/storefront';
import { index as productsIndex } from '@/routes/products';
import {
    ArrowRightIcon,
    LeafIcon,
    RabbitIcon,
    RecycleIcon,
    TruckIcon,
} from '@lucide/vue';

defineOptions({
    layout: StorefrontLayout,
});

defineProps<HomePageProps>();
</script>

<template>
    <Head title="Botanical Skincare & Conscious Beauty — Green Veil">
        <meta
            name="description"
            content="Discover pure botanical skincare and artisan cosmetics formulated with active herbal extracts and sustainable apothecary rituals."
        />
        <meta
            property="og:title"
            content="Green Veil — Botanical Skincare & Conscious Beauty"
        />
        <meta
            property="og:description"
            content="Discover pure botanical skincare and artisan cosmetics formulated with active herbal extracts and sustainable apothecary rituals."
        />
        <meta property="og:type" content="website" />
    </Head>

    <!-- Hero Slider Section -->
    <HeroSlider :slides="heroSlides" />

    <!-- Brand Values / Trust Points -->
    <section
        aria-label="Brand Pillars"
        class="border-b border-cream-200 bg-cream-100/50 py-8"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-4 sm:gap-8 lg:grid-cols-4">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-sage-100 text-sage-700"
                    >
                        <LeafIcon
                            class="h-5 w-5"
                            :stroke-width="1.5"
                            aria-hidden="true"
                        />
                    </div>
                    <div>
                        <h4
                            class="text-xs font-semibold tracking-wider text-charcoal-900 uppercase"
                        >
                            Pure Botanicals
                        </h4>
                        <p class="text-charcoal-600 text-[11px]">
                            Active plant extracts & oils
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-sage-100 text-sage-700"
                    >
                        <RabbitIcon
                            class="h-5 w-5"
                            :stroke-width="1.5"
                            aria-hidden="true"
                        />
                    </div>
                    <div>
                        <h4
                            class="text-xs font-semibold tracking-wider text-charcoal-900 uppercase"
                        >
                            Cruelty Free
                        </h4>
                        <p class="text-charcoal-600 text-[11px]">
                            Never tested on animals
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-sage-100 text-sage-700"
                    >
                        <RecycleIcon
                            class="h-5 w-5"
                            :stroke-width="1.5"
                            aria-hidden="true"
                        />
                    </div>
                    <div>
                        <h4
                            class="text-xs font-semibold tracking-wider text-charcoal-900 uppercase"
                        >
                            Sustainably Made
                        </h4>
                        <p class="text-charcoal-600 text-[11px]">
                            Recyclable packaging & jars
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-sage-100 text-sage-700"
                    >
                        <TruckIcon
                            class="h-5 w-5"
                            :stroke-width="1.5"
                            aria-hidden="true"
                        />
                    </div>
                    <div>
                        <h4
                            class="text-xs font-semibold tracking-wider text-charcoal-900 uppercase"
                        >
                            Free Delivery
                        </h4>
                        <p class="text-charcoal-600 text-[11px]">
                            On all orders over €50
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Active Promotions -->
    <PromoBanner :promotions="promotions" />

    <!-- Featured Categories Section -->
    <section
        v-if="featuredCategories.length > 0"
        class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8"
    >
        <div
            class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end"
        >
            <div>
                <span
                    class="mb-1 block text-xs font-semibold tracking-widest text-sage-600 uppercase"
                >
                    Curated Collections
                </span>
                <h2
                    class="font-serif text-2xl font-normal text-charcoal-900 sm:text-3xl"
                >
                    Botanical Rituals
                </h2>
            </div>
            <Link
                :href="productsIndex.url()"
                class="group inline-flex items-center gap-1.5 text-xs font-semibold tracking-wider text-sage-700 uppercase hover:text-sage-900"
            >
                <span>View All Collections</span>
                <ArrowRightIcon
                    class="h-4 w-4 transition-transform group-hover:translate-x-1"
                    aria-hidden="true"
                />
            </Link>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <CategoryCard
                v-for="category in featuredCategories"
                :key="category.id"
                :category="category"
            />
        </div>
    </section>

    <!-- Featured Formulations Section -->
    <section
        v-if="!featuredProducts || featuredProducts.length > 0"
        class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8"
    >
        <div
            class="mb-10 flex flex-col justify-between gap-4 sm:flex-row sm:items-end"
        >
            <div>
                <span
                    class="mb-1 block text-xs font-semibold tracking-widest text-sage-600 uppercase"
                >
                    Most Loved
                </span>
                <h2
                    class="font-serif text-2xl font-normal text-charcoal-900 sm:text-3xl"
                >
                    Signature Formulations
                </h2>
            </div>
            <Link
                :href="productsIndex.url()"
                class="group inline-flex items-center gap-1.5 text-xs font-semibold tracking-wider text-sage-700 uppercase hover:text-sage-900"
            >
                <span>Explore Catalog</span>
                <ArrowRightIcon
                    class="h-4 w-4 transition-transform group-hover:translate-x-1"
                    aria-hidden="true"
                />
            </Link>
        </div>

        <WhenVisible data="featuredProducts">
            <template #fallback>
                <ProductGridSkeleton :count="8" />
            </template>
            <template #default>
                <ProductGrid
                    v-if="featuredProducts && featuredProducts.length > 0"
                    :products="featuredProducts"
                />
            </template>
        </WhenVisible>
    </section>

    <!-- Brand Story / Philosophy Section -->
    <section class="mt-12 bg-sage-900 py-20 text-cream-50">
        <div class="mx-auto max-w-4xl space-y-6 px-6 text-center sm:px-8">
            <span
                class="block text-xs font-semibold tracking-widest text-sage-300 uppercase"
            >
                Our Philosophy
            </span>
            <h2
                class="font-serif text-3xl leading-tight font-normal text-cream-50 sm:text-4xl md:text-5xl"
            >
                Simplicity. Patience. Plant Alchemy.
            </h2>
            <p
                class="mx-auto max-w-2xl text-sm leading-relaxed font-light text-cream-200/90 sm:text-base"
            >
                Every Green Veil creation is grounded in the rhythm of nature.
                We combine cold-pressed botanical oils, healing herbal extracts,
                and therapeutic floral waters to support your skin's innate
                vitality without synthetic fillers.
            </p>
            <div class="pt-4">
                <Link
                    :href="productsIndex.url()"
                    class="inline-block rounded-full bg-cream-50 px-8 py-3.5 text-xs font-semibold tracking-widest text-sage-900 uppercase shadow-md transition-colors hover:bg-cream-100"
                >
                    Explore The Collection
                </Link>
            </div>
        </div>
    </section>
</template>
