<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { SearchInput } from '@/components/form';
import MobileNavigationDrawer from '@/components/storefront/MobileNavigationDrawer.vue';
import type { SharedProps } from '@/types/storefront';
import { home, login, logout } from '@/routes';
import { index as productsIndex } from '@/routes/products';
import { show as categoryShow } from '@/actions/App/Http/Controllers/CategoryController';
import { index as cartIndex } from '@/routes/cart';
import { index as favouritesIndex } from '@/routes/favourites';
import { profile } from '@/routes/account';
import { getNavigationLayout } from '@/lib/navigation';

const page = usePage<SharedProps>();
const mobileMenuOpen = ref(false);
const searchOpen = ref(false);
const searchQuery = ref('');

const user = computed(() => page.props.auth?.user);
const navigation = computed(() => page.props.navigation || []);
const cartCount = computed(() => page.props.cart?.count ?? 0);
const favouritesCount = computed(() => page.props.favourites?.length ?? 0);
const flash = computed(() => page.props.flash || {});
const isDemoMode = computed(() => Boolean(page.props.demo_mode));
const demoNotice = computed(
    () =>
        page.props.demo_notice ||
        'This website is for demonstration purposes only. Payments are not processed and orders will not be fulfilled.',
);

const showToast = ref(false);
const toastMessage = ref('');
const toastType = ref<'success' | 'error'>('success');

watch(
    () => page.props.flash,
    (newFlash) => {
        if (newFlash?.success) {
            toastMessage.value = newFlash.success;
            toastType.value = 'success';
            showToast.value = true;
            setTimeout(() => {
                showToast.value = false;
            }, 4000);
        } else if (newFlash?.error) {
            toastMessage.value = newFlash.error;
            toastType.value = 'error';
            showToast.value = true;
            setTimeout(() => {
                showToast.value = false;
            }, 5000);
        }
    },
    { immediate: true, deep: true },
);

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
                Botanical formulation, conscious ingredients &bull;
                Complimentary standard delivery over €50
            </span>
        </aside>

        <!-- Main Header -->
        <header
            class="sticky top-0 z-40 border-b border-cream-200 bg-cream-50/95 backdrop-blur-md transition-colors"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-20 items-center justify-between">
                    <!-- Mobile Menu Button -->
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
                    <div class="flex items-center">
                        <Link :href="home.url()" class="group flex flex-col">
                            <span
                                class="font-serif text-2xl tracking-tight text-sage-900 transition-colors group-hover:text-sage-700 sm:text-3xl"
                                >Green Veil</span
                            >
                            <span
                                class="-mt-1 text-[10px] tracking-widest text-sage-600 uppercase"
                                >Botanical Apothecary</span
                            >
                        </Link>
                    </div>

                    <!-- Desktop Navigation Links -->
                    <nav
                        class="hidden items-center space-x-8 text-sm font-medium tracking-wide lg:flex"
                        aria-label="Primary"
                    >
                        <Link
                            :href="productsIndex.url()"
                            class="border-b-2 border-transparent py-1 text-charcoal-800 transition-colors hover:border-sage-500 hover:text-sage-700"
                        >
                            All Products
                        </Link>
                        <div
                            v-for="cat in navigation"
                            :key="cat.id"
                            class="group relative shrink-0"
                        >
                            <Link
                                :href="categoryShow.url(cat.slug)"
                                class="inline-flex items-center gap-1 border-b-2 border-transparent py-1 text-charcoal-800 transition-colors hover:border-sage-500 hover:text-sage-700"
                            >
                                <span>{{ cat.name }}</span>
                                <svg
                                    v-if="
                                        cat.children && cat.children.length > 0
                                    "
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

                            <!-- Submenu if category has children -->
                            <div
                                v-if="cat.children && cat.children.length > 0"
                                class="pointer-events-none absolute top-full z-50 pt-3 opacity-0 transition-all duration-200 ease-out group-hover:pointer-events-auto group-hover:opacity-100"
                                :class="
                                    getNavigationLayout(cat).containerWidthClass
                                "
                            >
                                <div
                                    class="rounded-2xl border border-cream-200/90 bg-cream-50/98 p-5 shadow-2xl backdrop-blur-md"
                                >
                                    <!-- Mega Menu Header (for large departments) -->
                                    <div
                                        v-if="
                                            getNavigationLayout(cat).isMegaMenu
                                        "
                                        class="mb-4 flex items-center justify-between border-b border-cream-200/80 pb-3"
                                    >
                                        <div class="flex items-center gap-2.5">
                                            <h3
                                                class="font-serif text-base font-medium text-sage-900"
                                            >
                                                {{ cat.name }} Department
                                            </h3>
                                            <span
                                                class="rounded-full bg-sage-100 px-2.5 py-0.5 text-[11px] font-semibold text-sage-800"
                                            >
                                                {{ cat.children.length }}
                                                Subcategories
                                            </span>
                                        </div>
                                        <Link
                                            :href="categoryShow.url(cat.slug)"
                                            class="group/link flex items-center gap-1 text-xs font-semibold text-sage-700 hover:text-sage-900"
                                        >
                                            <span
                                                >Explore All
                                                {{ cat.name }}</span
                                            >
                                            <span
                                                class="transition-transform group-hover/link:translate-x-0.5"
                                                >&rarr;</span
                                            >
                                        </Link>
                                    </div>

                                    <!-- Multi-Column Grid dynamically balanced -->
                                    <div
                                        class="grid max-h-[68vh] gap-x-6 gap-y-2 overflow-y-auto pr-1"
                                        :class="
                                            getNavigationLayout(cat)
                                                .gridColsClass
                                        "
                                    >
                                        <div
                                            v-for="(
                                                column, colIdx
                                            ) in getNavigationLayout(cat)
                                                .columns"
                                            :key="colIdx"
                                            class="space-y-1"
                                        >
                                            <Link
                                                v-for="child in column"
                                                :key="child.id"
                                                :href="
                                                    categoryShow.url(child.slug)
                                                "
                                                class="block rounded-md px-2.5 py-1.5 text-xs text-charcoal-700 transition-colors hover:bg-sage-100/70 hover:text-sage-900"
                                            >
                                                {{ child.name }}
                                            </Link>
                                        </div>
                                    </div>

                                    <!-- Bottom quick bar for mega-menu -->
                                    <div
                                        v-if="
                                            getNavigationLayout(cat).isMegaMenu
                                        "
                                        class="text-charcoal-500 mt-4 flex items-center justify-between border-t border-cream-200/80 pt-3 text-[11px]"
                                    >
                                        <span
                                            >Clean botanical ingredients &bull;
                                            100% cruelty-free</span
                                        >
                                        <Link
                                            :href="categoryShow.url(cat.slug)"
                                            class="font-medium text-sage-700 hover:text-sage-900 hover:underline"
                                        >
                                            Shop full collection &rarr;
                                        </Link>
                                    </div>

                                    <!-- Simple view all link for smaller categories -->
                                    <div
                                        v-else
                                        class="mt-2.5 border-t border-cream-200/80 pt-2"
                                    >
                                        <Link
                                            :href="categoryShow.url(cat.slug)"
                                            class="flex items-center justify-between rounded-md px-2 py-1 text-xs font-semibold text-sage-700 hover:bg-sage-50 hover:text-sage-900"
                                        >
                                            <span>All {{ cat.name }}</span>
                                            <span>&rarr;</span>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </nav>

                    <!-- Utility Actions (Search, Favourites, Cart, Account) -->
                    <div class="flex items-center space-x-2 sm:space-x-4">
                        <!-- Search Toggle Button -->
                        <button
                            type="button"
                            class="rounded-full p-2 text-charcoal-700 transition-colors hover:bg-cream-100 hover:text-sage-700"
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
                            :href="favouritesIndex.url()"
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
                                :aria-label="
                                    user ? 'Account profile' : 'Sign in'
                                "
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
                                    v-if="user"
                                    class="hidden max-w-[100px] truncate text-xs font-medium sm:inline"
                                >
                                    {{ user.name.split(' ')[0] }}
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

                <!-- Search Input Bar Dropdown -->
                <div v-if="searchOpen" class="border-t border-cream-200 py-3">
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
        </header>

        <!-- Mobile Navigation Drawer -->
        <MobileNavigationDrawer
            v-model:open="mobileMenuOpen"
            :navigation="navigation"
            :user="user"
            :cart-count="cartCount"
            :favourites-count="favouritesCount"
        />

        <!-- Flash Toast Notification -->
        <transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showToast"
                class="pointer-events-auto fixed right-6 bottom-6 z-50 flex w-full max-w-sm items-start gap-3 rounded-xl border p-4 shadow-lg"
                :class="
                    toastType === 'success'
                        ? 'border-sage-700 bg-sage-900 text-cream-50'
                        : 'border-red-700 bg-red-900 text-white'
                "
                role="alert"
            >
                <div class="shrink-0 pt-0.5">
                    <svg
                        v-if="toastType === 'success'"
                        class="h-5 w-5 text-sage-300"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    <svg
                        v-else
                        class="h-5 w-5 text-red-300"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                </div>
                <div class="flex-1 text-sm leading-5">
                    {{ toastMessage }}
                </div>
                <button
                    type="button"
                    @click="showToast = false"
                    class="-mt-1 -mr-1 shrink-0 p-1 text-cream-200 hover:text-white"
                    aria-label="Dismiss notification"
                >
                    &times;
                </button>
            </div>
        </transition>

        <!-- Main Content Slot -->
        <main id="main-content" class="grow">
            <slot />
        </main>

        <!-- Storefront Footer -->
        <footer class="mt-18 border-t border-cream-200 bg-cream-100 pt-4 pb-4">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-10 md:grid-cols-4">
                    <!-- Brand Ethos -->
                    <div class="space-y-3 md:col-span-1">
                        <span class="block font-serif text-2xl text-sage-900"
                            >Green Veil</span
                        >
                        <p class="text-xs leading-relaxed text-charcoal-700">
                            Thoughtfully formulated botanical skincare and
                            cosmetics. Rooted in herbal tradition, refined by
                            gentle science.
                        </p>
                    </div>

                    <!-- Category Links -->
                    <div class="space-y-3">
                        <h2
                            class="text-xs font-semibold tracking-wider text-sage-800 uppercase"
                        >
                            Shop Collections
                        </h2>
                        <ul class="space-y-2 text-sm text-charcoal-700">
                            <li>
                                <Link
                                    :href="productsIndex.url()"
                                    class="transition-colors hover:text-sage-700"
                                    >All Products</Link
                                >
                            </li>
                            <li v-for="cat in navigation" :key="cat.id">
                                <Link
                                    :href="categoryShow.url(cat.slug)"
                                    class="transition-colors hover:text-sage-700"
                                >
                                    {{ cat.name }}
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Customer Care -->
                    <div class="space-y-3">
                        <h2
                            class="text-xs font-semibold tracking-wider text-sage-800 uppercase"
                        >
                            Customer Care
                        </h2>
                        <ul class="space-y-2 text-sm text-charcoal-700">
                            <li>
                                <Link
                                    :href="cartIndex.url()"
                                    class="transition-colors hover:text-sage-700"
                                    >Shopping Cart</Link
                                >
                            </li>
                            <li>
                                <Link
                                    :href="favouritesIndex.url()"
                                    class="transition-colors hover:text-sage-700"
                                    >Favourites</Link
                                >
                            </li>
                            <li>
                                <Link
                                    :href="user ? profile.url() : login.url()"
                                    class="transition-colors hover:text-sage-700"
                                >
                                    {{ user ? 'My Profile' : 'Sign In' }}
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Botanical Promise / Newsletter -->
                    <div class="space-y-3">
                        <h2
                            class="text-xs font-semibold tracking-wider text-sage-800 uppercase"
                        >
                            Our Promise
                        </h2>
                        <p class="text-xs leading-relaxed text-charcoal-700">
                            100% cruelty-free, recyclable glass packaging, and
                            clean botanical actives with total transparency.
                        </p>
                    </div>
                </div>

                <div
                    class="text-charcoal-500 mt-12 flex flex-col items-center justify-between gap-4 border-t border-cream-200 p-2 text-xs sm:flex-row"
                >
                    <p>
                        &copy; {{ new Date().getFullYear() }} Green Veil
                        Cosmetics. All rights reserved.
                    </p>
                    <p class="flex items-center gap-4">
                        <span>Pure botanical care</span>
                        <span>&bull;</span>
                        <span>Cruelty Free</span>
                        <span>&bull;</span>
                        <span>Sustainably Sourced</span>
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
