<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import StorefrontHeader from '@/components/storefront/layout/StorefrontHeader.vue';
import StorefrontFooter from '@/components/storefront/layout/StorefrontFooter.vue';
import ToastNotification from '@/components/storefront/layout/ToastNotification.vue';
import MobileNavigationDrawer from '@/components/storefront/MobileNavigationDrawer.vue';
import MiniCartDrawer from '@/components/storefront/cart/MiniCartDrawer.vue';
import type { SharedProps } from '@/types/storefront';
import { useCart } from '@/composables/useCart';
import { useFavourites } from '@/composables/useFavourites';

const page = usePage<SharedProps>();
const mobileMenuOpen = ref(false);
const isHydrated = ref(false);
const cart = useCart();
const favourites = useFavourites();

onMounted(() => {
    isHydrated.value = true;

    // Items saved in this browser are ready immediately; marking the cart initialised lets
    // the mini bag show them on every page, not only on the bag and checkout pages (which
    // initialise first with their full server payload, so this call is then a no-op).
    cart.initFromServer([]);

    if (page.props.favourites) {
        favourites.initFromServer(page.props.favourites);
    }
});

const user = computed(() => page.props.auth?.user);
const navigation = computed(() => page.props.navigation || []);
const cartCount = computed(() => {
    return cart.count.value > 0
        ? cart.count.value
        : (page.props.cart?.count ?? 0);
});
const favouritesCount = computed(() => {
    return favourites.productIds.value.length > 0
        ? favourites.count.value
        : (page.props.favourites?.length ?? 0);
});
</script>

<template>
    <div
        :data-hydrated="isHydrated"
        class="flex min-h-screen flex-col bg-surface font-sans text-ink selection:bg-rose-200 selection:text-rose-900"
    >
        <!-- Skip to Main Content Link for Screen Readers & Keyboard Navigation -->
        <a
            href="#main-content"
            class="sr-only z-50 rounded-full bg-rose-900 px-6 py-3 text-xs font-semibold tracking-wider text-cream-50 uppercase shadow-lg focus:not-sr-only focus:fixed focus:top-4 focus:left-4"
        >
            Skip to main content
        </a>

        <!-- Main Header & Announcement Bar -->
        <StorefrontHeader
            v-model:mobile-menu-open="mobileMenuOpen"
            class="print:hidden"
        />

        <!-- Mobile Navigation Drawer -->
        <MobileNavigationDrawer
            v-model:open="mobileMenuOpen"
            :navigation="navigation"
            :user="user"
            :cart-count="cartCount"
            :favourites-count="favouritesCount"
            class="print:hidden"
        />

        <!-- Flash Toast Notification -->
        <ToastNotification class="print:hidden" />

        <!-- Mini-Cart Slideout Drawer -->
        <MiniCartDrawer class="print:hidden" />

        <!-- Main Content Slot -->
        <main
            id="main-content"
            tabindex="-1"
            class="grow outline-none print:p-0"
        >
            <slot />
        </main>

        <!-- Storefront Footer -->
        <StorefrontFooter class="print:hidden" />
    </div>
</template>
