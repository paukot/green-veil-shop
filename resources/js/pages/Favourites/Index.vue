<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AccountLayout from '@/layouts/AccountLayout.vue';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import WishlistContent from '@/components/storefront/WishlistContent.vue';
import type { CustomerUser, ProductCardData } from '@/types/storefront';
import { useFavourites } from '@/composables/useFavourites';

defineOptions({
    layout: (pageProps: { auth?: { user?: CustomerUser | null } }) =>
        pageProps.auth?.user ? AccountLayout : StorefrontLayout,
});

const page = usePage<{ auth: { user: CustomerUser | null } }>();
const user = computed(() => page.props.auth?.user);

const props = defineProps<{
    products: ProductCardData[];
}>();

const favourites = useFavourites();

onMounted(() => {
    if (props.products.length > 0) {
        favourites.initFromServer(props.products.map((p) => p.id));
    }
});

const displayedProducts = computed(() => {
    return props.products.filter((p) => favourites.isFavourited(p.id));
});
</script>

<template>
    <!-- Authenticated View: rendered inside AccountLayout (User Settings Layout) -->
    <template v-if="user">
        <Head title="Wishlist | Green Veil" />

        <div class="space-y-6">
            <WishlistContent
                :products="displayedProducts"
                empty-title="Your ritual list is empty"
            />
        </div>
    </template>

    <!-- Guest View: rendered inside StorefrontLayout -->
    <template v-else>
        <Head title="Your Wishlist | Saved Botanicals | Green Veil" />

        <!-- Header Banner -->
        <section
            class="border-b border-cream-200 bg-linear-to-b from-cream-100 to-cream-50 py-10 sm:py-14"
        >
            <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <span
                    class="text-xs font-semibold tracking-widest text-sage-700 uppercase"
                >
                    Your Personal Wishlist
                </span>
                <h1
                    class="mt-2 font-serif text-3xl tracking-tight text-charcoal-900 sm:text-4xl"
                >
                    Saved Botanicals
                </h1>
                <p
                    class="text-charcoal-600 mt-3 text-sm leading-relaxed font-light"
                >
                    A conscious curation of your favourite botanical
                    formulations, gentle mists, and daily self-care rituals.
                </p>
            </div>
        </section>

        <!-- Main Favourites Content -->
        <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-16 lg:px-8">
            <WishlistContent
                :products="displayedProducts"
                :show-browse-link="true"
                empty-title="Your wishlist is empty"
            />
        </section>
    </template>
</template>
