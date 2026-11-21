<script setup lang="ts">
import { computed } from 'vue';
import { Head, WhenVisible } from '@inertiajs/vue3';
import {
    ArrowRightIcon,
    LeafIcon,
    RabbitIcon,
    RecycleIcon,
    TruckIcon,
} from '@lucide/vue';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import CategoryCard from '@/components/storefront/CategoryCard.vue';
import HeroSlider from '@/components/storefront/HeroSlider.vue';
import ProductGrid from '@/components/storefront/ProductGrid.vue';
import PromoBanner from '@/components/storefront/PromoBanner.vue';
import ProductGridSkeleton from '@/components/storefront/skeletons/ProductGridSkeleton.vue';
import Button from '@/components/ui/Button.vue';
import Container from '@/components/ui/Container.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Section from '@/components/ui/Section.vue';
import type { HomePageProps } from '@/types/storefront';
import { index as productsIndex } from '@/routes/products';
import { resolveStorageUrl, showPlaceholderOnError } from '@/lib/utils';

defineOptions({
    layout: StorefrontLayout,
});

const props = defineProps<HomePageProps>();

const pillars = [
    {
        icon: LeafIcon,
        title: 'Pure botanicals',
        description: 'Active plant extracts and cold-pressed oils',
    },
    {
        icon: RabbitIcon,
        title: 'Cruelty free',
        description: 'Never tested on animals, ever',
    },
    {
        icon: RecycleIcon,
        title: 'Sustainably made',
        description: 'Recyclable glass and refillable jars',
    },
    {
        icon: TruckIcon,
        title: 'Free delivery',
        description: 'On every order over €50',
    },
];

/** The brand story borrows a department photo so it always has a local image. */
const storyImage = computed(() =>
    resolveStorageUrl(
        props.featuredCategories.find((category) => category.image)?.image,
    ),
);
</script>

<template>
    <Head title="Botanical Skincare & Conscious Beauty">
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

    <HeroSlider :slides="heroSlides" />

    <!-- Pillars -->
    <section aria-label="Our promise" class="border-y border-border">
        <Container>
            <ul
                class="grid grid-cols-1 gap-6 py-8 sm:grid-cols-2 lg:grid-cols-4 lg:gap-8"
            >
                <li
                    v-for="pillar in pillars"
                    :key="pillar.title"
                    class="flex items-center gap-4"
                >
                    <span
                        class="flex size-12 shrink-0 items-center justify-center rounded-full bg-sage-100 text-sage-800"
                    >
                        <component
                            :is="pillar.icon"
                            class="size-6"
                            aria-hidden="true"
                        />
                    </span>
                    <div class="flex flex-col gap-1">
                        <p class="text-sm font-semibold text-ink">
                            {{ pillar.title }}
                        </p>
                        <p class="text-xs text-ink-muted">
                            {{ pillar.description }}
                        </p>
                    </div>
                </li>
            </ul>
        </Container>
    </section>

    <!-- Departments -->
    <Section v-if="featuredCategories.length > 0">
        <div class="flex flex-col gap-8 lg:gap-12">
            <PageHeader
                level="section"
                eyebrow="Departments"
                title="Shop by ritual"
                description="From morning cleansers to evening fragrance, find the step your routine is missing."
            >
                <template #actions>
                    <Button :href="productsIndex.url()" variant="link">
                        View all
                        <template #trailing>
                            <ArrowRightIcon class="size-4" aria-hidden="true" />
                        </template>
                    </Button>
                </template>
            </PageHeader>
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 lg:gap-6">
                <CategoryCard
                    v-for="category in featuredCategories"
                    :key="category.id"
                    :category="category"
                />
            </div>
        </div>
    </Section>

    <PromoBanner :promotions="promotions" />

    <!-- Bestsellers -->
    <Section>
        <div class="flex flex-col gap-8 lg:gap-12">
            <PageHeader
                level="section"
                eyebrow="Bestsellers"
                title="Loved by our community"
                description="The formulas customers come back for, season after season."
            >
                <template #actions>
                    <Button :href="productsIndex.url()" variant="link">
                        Shop all
                        <template #trailing>
                            <ArrowRightIcon class="size-4" aria-hidden="true" />
                        </template>
                    </Button>
                </template>
            </PageHeader>
            <WhenVisible data="featuredProducts">
                <template #fallback>
                    <ProductGridSkeleton :count="8" />
                </template>
                <ProductGrid
                    v-if="featuredProducts"
                    :products="featuredProducts"
                />
            </WhenVisible>
        </div>
    </Section>

    <!-- Brand story -->
    <Section tone="dark" spacing="none">
        <div
            class="grid items-center gap-8 py-16 lg:grid-cols-2 lg:gap-16 lg:py-24"
        >
            <div
                class="aspect-[4/3] overflow-hidden rounded-2xl bg-sage-900 lg:aspect-square"
            >
                <img
                    v-if="storyImage"
                    :src="storyImage"
                    alt=""
                    class="size-full object-cover"
                    loading="lazy"
                    @error="showPlaceholderOnError"
                />
            </div>
            <div class="flex flex-col gap-6">
                <p
                    class="text-xs font-semibold tracking-widest text-sage-300 uppercase"
                >
                    Our philosophy
                </p>
                <h2
                    class="font-serif text-4xl tracking-tight text-balance text-cream-50 lg:text-6xl"
                >
                    Simplicity. Patience.
                    <em class="text-sage-300">Plant alchemy.</em>
                </h2>
                <p class="max-w-lg text-base text-cream-200">
                    Every Green Veil creation follows the rhythm of nature. We
                    combine cold-pressed botanical oils, healing herbal extracts
                    and floral waters to support your skin's own vitality,
                    without synthetic fillers.
                </p>
                <div>
                    <Button
                        :href="productsIndex.url()"
                        variant="secondary"
                        size="lg"
                    >
                        Explore the collection
                    </Button>
                </div>
            </div>
        </div>
    </Section>
</template>
