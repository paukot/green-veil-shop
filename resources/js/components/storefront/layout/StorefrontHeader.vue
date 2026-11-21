<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import MegaMenuDropdown from '@/components/storefront/layout/MegaMenuDropdown.vue';
import LiveSearchAutocomplete from '@/components/storefront/layout/LiveSearchAutocomplete.vue';
import type { SharedProps } from '@/types/storefront';
import { home, login, logout } from '@/routes';
import { index as productsIndex } from '@/routes/products';
import { show as categoryShow } from '@/actions/App/Http/Controllers/CategoryController';
import { index as favouritesIndex } from '@/routes/favourites';
import { profile, rituals as accountRituals } from '@/routes/account';
import { getNavigationLayout } from '@/lib/navigation';

import { useCart } from '@/composables/useCart';
import { useFavourites } from '@/composables/useFavourites';
import {
    ChevronDownIcon,
    HeartIcon,
    MenuIcon,
    SearchIcon,
    ShoppingBagIcon,
    UserIcon,
    XIcon,
} from '@lucide/vue';

const mobileMenuOpen = defineModel<boolean>('mobileMenuOpen', {
    default: false,
});

const page = usePage<SharedProps>();
const searchOpen = ref(false);
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
                            class="-ml-2 flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md p-2 text-charcoal-700 hover:text-sage-700 focus:ring-2 focus:ring-sage-500 focus:outline-none"
                            aria-label="Toggle navigation menu"
                            :aria-expanded="mobileMenuOpen"
                            @click="mobileMenuOpen = !mobileMenuOpen"
                        >
                            <MenuIcon
                                v-if="!mobileMenuOpen"
                                class="h-6 w-6"
                                :stroke-width="1.5"
                                aria-hidden="true"
                            />
                            <XIcon
                                v-else
                                class="h-6 w-6"
                                :stroke-width="1.5"
                                aria-hidden="true"
                            />
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

                <!-- Center: Live Search Autocomplete (Visible on desktop lg+) -->
                <div
                    class="hidden max-w-md flex-1 items-center lg:flex xl:max-w-lg"
                >
                    <LiveSearchAutocomplete
                        size="sm"
                        placeholder="Search botanicals, perfumes, skincare..."
                    />
                </div>

                <!-- Right: Utility Actions (Search Toggle, Favourites, Cart, Account) -->
                <div class="flex shrink-0 items-center space-x-1 sm:space-x-3">
                    <!-- Search Toggle Button (Mobile & Tablet only) -->
                    <button
                        type="button"
                        class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full p-2 text-charcoal-700 transition-colors hover:bg-cream-100 hover:text-sage-700 lg:hidden"
                        aria-label="Search products"
                        @click="searchOpen = !searchOpen"
                    >
                        <SearchIcon
                            class="h-5 w-5"
                            :stroke-width="1.5"
                            aria-hidden="true"
                        />
                    </button>

                    <!-- Favourites Link -->
                    <Link
                        :href="
                            user ? accountRituals.url() : favouritesIndex.url()
                        "
                        class="relative flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full p-2 transition-colors hover:bg-cream-100 hover:text-sage-700"
                        :class="
                            $page.url.startsWith(favouritesIndex.url()) ||
                            $page.url.startsWith(accountRituals.url())
                                ? 'bg-cream-100 text-sage-800'
                                : 'text-charcoal-700'
                        "
                        aria-label="View favourites"
                    >
                        <HeartIcon
                            class="h-5 w-5"
                            :stroke-width="1.5"
                            aria-hidden="true"
                        />
                        <span
                            v-if="favouritesCount > 0"
                            class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-sage-600 text-[10px] font-medium text-white"
                        >
                            {{ favouritesCount }}
                        </span>
                    </Link>

                    <!-- Cart Trigger Button -->
                    <button
                        type="button"
                        @click="cart.openDrawer()"
                        class="relative flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full p-2 text-charcoal-700 transition-colors hover:bg-cream-100 hover:text-sage-700 focus:outline-none"
                        aria-label="View shopping bag"
                    >
                        <ShoppingBagIcon
                            class="h-5 w-5"
                            :stroke-width="1.5"
                            aria-hidden="true"
                        />
                        <span
                            v-if="cartCount > 0"
                            class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-sage-700 text-[10px] font-medium text-white"
                        >
                            {{ cartCount }}
                        </span>
                    </button>

                    <!-- Customer Account -->
                    <div class="group relative">
                        <Link
                            :href="user ? profile.url() : login.url()"
                            class="flex min-h-[44px] items-center gap-1.5 rounded-full p-2 text-charcoal-700 transition-colors hover:bg-cream-100 hover:text-sage-700"
                            :aria-label="user ? 'Account profile' : 'Sign in'"
                        >
                            <UserIcon
                                class="h-5 w-5"
                                :stroke-width="1.5"
                                aria-hidden="true"
                            />
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
                <div class="mx-auto max-w-xl">
                    <LiveSearchAutocomplete
                        size="sm"
                        placeholder="Search botanicals, formulations..."
                        :autofocus="true"
                        @close="searchOpen = false"
                    />
                </div>
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
                            <ChevronDownIcon
                                v-if="cat.children && cat.children.length > 0"
                                class="text-charcoal-400 h-3.5 w-3.5 transition-transform duration-200 group-hover:rotate-180 group-hover:text-sage-700"
                                aria-hidden="true"
                            />
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
