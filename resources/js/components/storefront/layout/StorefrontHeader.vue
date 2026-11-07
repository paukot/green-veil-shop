<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { SearchInput } from '@/components/form';
import MegaMenuDropdown from '@/components/storefront/layout/MegaMenuDropdown.vue';
import type { SharedProps } from '@/types/storefront';
import { home, login, logout } from '@/routes';
import { index as productsIndex } from '@/routes/products';
import { show as categoryShow } from '@/actions/App/Http/Controllers/CategoryController';
import { index as cartIndex } from '@/routes/cart';
import { index as favouritesIndex } from '@/routes/favourites';
import { profile, rituals as accountRituals } from '@/routes/account';
import { getNavigationLayout } from '@/lib/navigation';

import { useCart } from '@/composables/useCart';
import { useFavourites } from '@/composables/useFavourites';

const mobileMenuOpen = defineModel<boolean>('mobileMenuOpen', {
    default: false,
});

const page = usePage<SharedProps>();
const searchOpen = ref(false);
const searchQuery = ref('');
const cart = useCart();
const favourites = useFavourites();

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
const isDemoMode = computed(() => Boolean(page.props.demo_mode));
const demoNotice = computed(
    () =>
        page.props.demo_notice ||
        'This website is for demonstration purposes only. Payments are not processed and orders will not be fulfilled.',
);

const userFirstName = computed(() => {
    if (!user.value?.name) return '';
    return user.value.name.trim().split(' ')[0] ?? '';
});

function submitSearch() {
    if (!searchQuery.value.trim()) return;
    router.get(
        productsIndex.url(),
        { q: searchQuery.value.trim() },
        { preserveState: true },
    );
    searchOpen.value = false;
    mobileMenuOpen.value = false;
}
</script>

<template>
    <!-- Top Announcement Bar / Demo Mode Notice -->
    <aside
        aria-label="Announcement"
        :class="
            isDemoMode
                ? 'border-b border-amber-600/40 bg-amber-900 px-4 py-2 text-center text-xs tracking-wide text-amber-100 shadow-xs'
                : 'bg-sage-800 px-4 py-2 text-center text-xs tracking-wide text-cream-100'
        "
    >
        <div
            v-if="isDemoMode"
            class="flex flex-wrap items-center justify-center gap-2 font-medium"
        >
            <span
                class="rounded-sm bg-amber-950/80 px-2 py-0.5 text-[10px] font-bold tracking-wider text-amber-300 uppercase"
            >
                Demo Mode
            </span>
            <span>
                {{ demoNotice }}
            </span>
        </div>
        <span v-else>
            Botanical formulation, conscious ingredients &bull; Complimentary
            standard delivery over €50
        </span>
    </aside>

    <!-- Main Header -->
    <header
        class="sticky top-0 z-40 border-b border-cream-200 bg-cream-50/95 backdrop-blur-md transition-colors"
    >
        <!-- Tier 1: Brand Logo, Desktop Search & Utility Actions -->
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between gap-4 sm:h-18">
                <!-- Left: Mobile Menu Toggle & Brand Logo -->
                <div class="flex items-center gap-3">
                    <!-- Mobile Menu Button (lg:hidden) -->
                    <div class="flex items-center lg:hidden">
                        <button
                            type="button"
                            class="-ml-2 rounded-md p-2 text-charcoal-700 hover:text-sage-700 focus:ring-2 focus:ring-sage-500 focus:outline-none"
                            aria-label="Toggle navigation menu"
                            :aria-expanded="mobileMenuOpen"
                            @click="mobileMenuOpen = !mobileMenuOpen"
                        >
                            <svg
                                v-if="!mobileMenuOpen"
                                class="h-6 w-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"
                                />
                            </svg>
                            <svg
                                v-else
                                class="h-6 w-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <!-- Brand Logo -->
                    <Link
                        :href="home.url()"
                        prefetch
                        class="group flex shrink-0 flex-col"
                    >
                        <span
                            class="font-serif text-2xl tracking-tight text-sage-900 transition-colors group-hover:text-sage-700 sm:text-3xl"
                        >
                            Green Veil
                        </span>
                        <span
                            class="-mt-1 text-[10px] tracking-widest text-sage-600 uppercase"
                        >
                            Botanical Apothecary
                        </span>
                    </Link>
                </div>

                <!-- Center: Search Input Bar (Visible on desktop lg+) -->
                <form
                    @submit.prevent="submitSearch"
                    class="hidden max-w-md flex-1 items-center lg:flex xl:max-w-lg"
                >
                    <SearchInput
                        v-model="searchQuery"
                        placeholder="Search botanicals, perfumes, skincare..."
                        size="sm"
                    />
                </form>

                <!-- Right: Utility Actions (Search Toggle, Favourites, Cart, Account) -->
                <div class="flex shrink-0 items-center space-x-1 sm:space-x-3">
                    <!-- Search Toggle Button (Mobile & Tablet only) -->
                    <button
                        type="button"
                        class="rounded-full p-2 text-charcoal-700 transition-colors hover:bg-cream-100 hover:text-sage-700 lg:hidden"
                        aria-label="Search products"
                        @click="searchOpen = !searchOpen"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"
                            />
                        </svg>
                    </button>

                    <!-- Favourites Link -->
                    <Link
                        :href="
                            user ? accountRituals.url() : favouritesIndex.url()
                        "
                        class="relative rounded-full p-2 text-charcoal-700 transition-colors hover:bg-cream-100 hover:text-sage-700"
                        aria-label="View favourites"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"
                            />
                        </svg>
                        <span
                            v-if="favouritesCount > 0"
                            class="absolute top-0 right-0 flex h-4 w-4 items-center justify-center rounded-full bg-sage-600 text-[10px] font-medium text-white"
                        >
                            {{ favouritesCount }}
                        </span>
                    </Link>

                    <!-- Cart Link -->
                    <Link
                        :href="cartIndex.url()"
                        class="relative rounded-full p-2 text-charcoal-700 transition-colors hover:bg-cream-100 hover:text-sage-700"
                        aria-label="View shopping cart"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25c-.67 0-1.19-.578-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"
                            />
                        </svg>
                        <span
                            v-if="cartCount > 0"
                            class="absolute top-0 right-0 flex h-4 w-4 items-center justify-center rounded-full bg-sage-700 text-[10px] font-medium text-white"
                        >
                            {{ cartCount }}
                        </span>
                    </Link>

                    <!-- Customer Account -->
                    <div class="group relative">
                        <Link
                            :href="user ? profile.url() : login.url()"
                            class="flex items-center gap-1.5 rounded-full p-2 text-charcoal-700 transition-colors hover:bg-cream-100 hover:text-sage-700"
                            :aria-label="user ? 'Account profile' : 'Sign in'"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"
                                />
                            </svg>
                            <span
                                v-if="userFirstName"
                                class="hidden max-w-[100px] truncate text-xs font-medium sm:inline"
                            >
                                {{ userFirstName }}
                            </span>
                        </Link>

                        <!-- User dropdown menu on hover if logged in -->
                        <div
                            v-if="user"
                            class="pointer-events-none absolute top-full right-0 z-50 w-44 pt-1.5 opacity-0 transition-all duration-150 group-hover:pointer-events-auto group-hover:opacity-100"
                        >
                            <div
                                class="rounded-lg border border-cream-200 bg-cream-50 py-1.5 text-xs shadow-lg"
                            >
                                <div
                                    class="border-b border-cream-200 px-3 py-1.5"
                                >
                                    <p
                                        class="truncate font-medium text-charcoal-900"
                                    >
                                        {{ user.name }}
                                    </p>
                                    <p
                                        class="text-charcoal-500 truncate text-[11px]"
                                    >
                                        {{ user.email }}
                                    </p>
                                </div>
                                <Link
                                    :href="profile.url()"
                                    class="block px-3 py-2 text-charcoal-700 transition-colors hover:bg-sage-50 hover:text-sage-800"
                                >
                                    My Profile
                                </Link>
                                <Link
                                    :href="logout.url()"
                                    method="post"
                                    as="button"
                                    class="w-full px-3 py-2 text-left text-red-600 transition-colors hover:bg-red-50 hover:text-red-700"
                                >
                                    Sign Out
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile / Tablet Search Input Dropdown -->
            <div
                v-if="searchOpen"
                class="border-t border-cream-200 py-3 lg:hidden"
            >
                <form
                    @submit.prevent="submitSearch"
                    class="mx-auto flex max-w-xl items-center gap-2"
                >
                    <SearchInput
                        v-model="searchQuery"
                        placeholder="Search by botanical name, concern, or product..."
                        autofocus
                        @clear="searchOpen = false"
                    />
                    <button
                        type="submit"
                        class="shrink-0 rounded-full bg-sage-700 px-5 py-2 text-xs font-medium tracking-wider text-white uppercase transition-colors hover:bg-sage-800"
                    >
                        Search
                    </button>
                </form>
            </div>
        </div>

        <!-- Tier 2: Category Navigation Bar (Desktop lg+) -->
        <nav
            class="hidden border-t border-cream-200/70 bg-cream-50/90 lg:block"
            aria-label="Primary"
        >
            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    class="flex h-11 items-center justify-center gap-6 text-xs font-medium tracking-wide xl:gap-8 xl:text-sm"
                >
                    <Link
                        :href="productsIndex.url()"
                        prefetch
                        class="border-b-2 border-transparent py-2.5 text-charcoal-800 transition-colors hover:border-sage-500 hover:text-sage-700"
                    >
                        All Products
                    </Link>

                    <div
                        v-for="cat in navigation"
                        :key="cat.id"
                        class="group py-2.5"
                        :class="{
                            relative: !getNavigationLayout(cat).isMegaMenu,
                        }"
                    >
                        <Link
                            :href="categoryShow.url(cat.slug)"
                            prefetch
                            class="inline-flex items-center gap-1 border-b-2 border-transparent text-charcoal-800 transition-colors hover:border-sage-500 hover:text-sage-700"
                        >
                            <span>{{ cat.name }}</span>
                            <svg
                                v-if="cat.children && cat.children.length > 0"
                                class="text-charcoal-400 h-3.5 w-3.5 transition-transform duration-200 group-hover:rotate-180 group-hover:text-sage-700"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </Link>

                        <!-- Submenu / Mega-Menu -->
                        <MegaMenuDropdown
                            v-if="cat.children && cat.children.length > 0"
                            :category="cat"
                        />
                    </div>
                </div>
            </div>
        </nav>
    </header>
</template>
