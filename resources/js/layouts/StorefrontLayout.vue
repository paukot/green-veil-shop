<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import StorefrontHeader from '@/components/storefront/layout/StorefrontHeader.vue';
import StorefrontFooter from '@/components/storefront/layout/StorefrontFooter.vue';
import ToastNotification from '@/components/storefront/layout/ToastNotification.vue';
import MobileNavigationDrawer from '@/components/storefront/MobileNavigationDrawer.vue';
import type { SharedProps } from '@/types/storefront';

const page = usePage<SharedProps>();
const mobileMenuOpen = ref(false);

const user = computed(() => page.props.auth?.user);
const navigation = computed(() => page.props.navigation || []);
const cartCount = computed(() => page.props.cart?.count ?? 0);
const favouritesCount = computed(() => page.props.favourites?.length ?? 0);
</script>

<template>
    <div
        class="flex min-h-screen flex-col bg-cream-50 font-sans text-charcoal-900 selection:bg-sage-200 selection:text-sage-900"
    >
        <!-- Skip to Main Content Link for Screen Readers & Keyboard Navigation -->
        <a
            href="#main-content"
            class="sr-only z-50 rounded-lg bg-sage-800 px-4 py-2 text-xs font-medium tracking-wider text-cream-50 uppercase shadow-lg ring-2 ring-sage-500 outline-none focus:not-sr-only focus:fixed focus:top-4 focus:left-4"
        >
            Skip to main content
        </a>

        <!-- Main Header & Announcement Bar -->
        <StorefrontHeader v-model:mobile-menu-open="mobileMenuOpen" />

        <!-- Mobile Navigation Drawer -->
        <MobileNavigationDrawer
            v-model:open="mobileMenuOpen"
            :navigation="navigation"
            :user="user"
            :cart-count="cartCount"
            :favourites-count="favouritesCount"
        />

        <!-- Flash Toast Notification -->
        <ToastNotification />

        <!-- Main Content Slot -->
        <main id="main-content" class="grow">
            <slot />
        </main>

        <!-- Storefront Footer -->
        <StorefrontFooter />
    </div>
</template>
