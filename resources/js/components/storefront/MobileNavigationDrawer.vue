<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { SearchInput } from '@/components/form';
import type { CategoryNavigationItem, CustomerUser } from '@/types/storefront';
import { home, login, logout, register } from '@/routes';
import { index as productsIndex } from '@/routes/products';
import { show as categoryShow } from '@/actions/App/Http/Controllers/CategoryController';
import { index as cartIndex } from '@/routes/cart';
import { index as favouritesIndex } from '@/routes/favourites';
import { profile, rituals as accountRituals } from '@/routes/account';
import { index as ordersIndex } from '@/routes/account/orders';

const open = defineModel<boolean>('open', { default: false });

const props = defineProps<{
    navigation: CategoryNavigationItem[];
    user: CustomerUser | null | undefined;
    cartCount: number;
    favouritesCount: number;
}>();

const searchQuery = ref('');
const expandedCategories = ref<Record<number, boolean>>({});

function toggleCategory(catId: number) {
    expandedCategories.value[catId] = !expandedCategories.value[catId];
}

function close() {
    open.value = false;
}

function submitSearch() {
    if (!searchQuery.value.trim()) return;
    router.get(
        productsIndex.url(),
        { q: searchQuery.value.trim() },
        { preserveState: true },
    );
    close();
}

watch(open, (isOpen) => {
    if (typeof document !== 'undefined') {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
});

let removeNavigateListener: (() => void) | null = null;

const isMounted = ref(false);
const isPanelVisible = ref(false);

function handleKeyDown(e: KeyboardEvent) {
    if (e.key === 'Escape' && open.value) {
        close();
    }
}

onMounted(() => {
    isMounted.value = true;
    window.addEventListener('keydown', handleKeyDown);
    removeNavigateListener = router.on('navigate', () => {
        close();
    });
});

onUnmounted(() => {
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
    window.removeEventListener('keydown', handleKeyDown);
    if (removeNavigateListener) {
        removeNavigateListener();
    }
});
</script>

<template>
    <Teleport v-if="isMounted" to="body">
        <div class="relative z-50 lg:hidden">
            <!-- Backdrop -->
            <Transition
                enter-active-class="transition-opacity ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="open"
                    class="fixed inset-0 cursor-pointer bg-charcoal-900/60 backdrop-blur-xs transition-opacity"
                    aria-hidden="true"
                    @click="close"
                />
            </Transition>

            <!-- Slide-out Drawer Panel -->
            <div
                v-show="open || isPanelVisible"
                class="pointer-events-none fixed inset-0 flex"
                @click.self="close"
            >
                <Transition
                    enter-active-class="transform transition ease-out duration-300"
                    enter-from-class="-translate-x-full"
                    enter-to-class="translate-x-0"
                    leave-active-class="transform transition ease-in duration-200"
                    leave-from-class="translate-x-0"
                    leave-to-class="-translate-x-full"
                    @before-enter="isPanelVisible = true"
                    @after-leave="isPanelVisible = false"
                >
                    <div
                        v-if="open"
                        class="pointer-events-auto relative flex h-full w-full max-w-sm flex-col bg-cream-50 shadow-2xl"
                        role="dialog"
                        aria-modal="true"
                        aria-label="Mobile Navigation"
                    >
                        <!-- Header with Brand & Close Button -->
                        <div
                            class="flex items-center justify-between border-b border-cream-200 px-6 py-5"
                        >
                            <Link
                                :href="home.url()"
                                class="flex flex-col"
                                @click="close"
                            >
                                <span
                                    class="font-serif text-2xl tracking-tight text-sage-900"
                                    >Green Veil</span
                                >
                                <span
                                    class="-mt-0.5 text-[10px] tracking-widest text-sage-600 uppercase"
                                    >Botanical Apothecary</span
                                >
                            </Link>

                            <button
                                type="button"
                                class="rounded-full p-2 text-charcoal-700 transition-colors hover:bg-cream-200/80 hover:text-charcoal-900 focus:ring-2 focus:ring-sage-500 focus:outline-hidden"
                                aria-label="Close navigation"
                                @click="close"
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
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>

                        <!-- Search Section -->
                        <div class="border-b border-cream-200 px-6 py-4">
                            <form
                                @submit.prevent="submitSearch"
                                class="relative flex items-center"
                            >
                                <SearchInput
                                    v-model="searchQuery"
                                    placeholder="Search botanicals, concerns..."
                                    size="sm"
                                    @clear="searchQuery = ''"
                                />
                            </form>
                        </div>

                        <!-- Scrollable Navigation Body -->
                        <div class="flex-1 overflow-y-auto px-6 py-4">
                            <!-- Main Links -->
                            <div class="space-y-1">
                                <Link
                                    :href="home.url()"
                                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-charcoal-800 transition-colors hover:bg-cream-100 hover:text-sage-800"
                                    @click="close"
                                >
                                    <svg
                                        class="h-4 w-4 text-sage-600"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.5"
                                            d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"
                                        />
                                    </svg>
                                    <span>Home</span>
                                </Link>

                                <Link
                                    :href="productsIndex.url()"
                                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-charcoal-800 transition-colors hover:bg-cream-100 hover:text-sage-800"
                                    @click="close"
                                >
                                    <svg
                                        class="h-4 w-4 text-sage-600"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.5"
                                            d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"
                                        />
                                    </svg>
                                    <span>All Botanicals</span>
                                </Link>
                            </div>

                            <!-- Collections / Categories Section -->
                            <div class="mt-6 border-t border-cream-200/80 pt-5">
                                <p
                                    class="px-3 text-[11px] font-semibold tracking-wider text-sage-700 uppercase"
                                >
                                    Botanical Collections
                                </p>

                                <nav class="mt-2 space-y-1.5">
                                    <div
                                        v-for="cat in props.navigation"
                                        :key="cat.id"
                                        class="overflow-hidden rounded-xl border transition-colors"
                                        :class="
                                            expandedCategories[cat.id]
                                                ? 'border-sage-200/90 bg-sage-50/50'
                                                : 'border-transparent'
                                        "
                                    >
                                        <!-- Row button for categories with children (entire row clickable) -->
                                        <button
                                            v-if="
                                                cat.children &&
                                                cat.children.length > 0
                                            "
                                            type="button"
                                            class="flex w-full items-center justify-between rounded-xl px-3.5 py-3 text-left transition-colors focus:outline-none"
                                            :class="
                                                expandedCategories[cat.id]
                                                    ? 'bg-sage-100/70 font-semibold text-sage-950'
                                                    : 'font-medium text-charcoal-800 hover:bg-cream-100/80'
                                            "
                                            :aria-expanded="
                                                expandedCategories[cat.id]
                                            "
                                            :aria-label="`Toggle ${cat.name} subcategories`"
                                            @click="toggleCategory(cat.id)"
                                        >
                                            <span
                                                class="text-sm tracking-wide"
                                                >{{ cat.name }}</span
                                            >

                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="rounded-full bg-cream-200/80 px-2 py-0.5 text-[10px] font-semibold text-sage-800"
                                                >
                                                    {{ cat.children.length }}
                                                </span>
                                                <span
                                                    class="flex h-7 w-7 items-center justify-center rounded-full transition-transform duration-200"
                                                    :class="
                                                        expandedCategories[
                                                            cat.id
                                                        ]
                                                            ? 'rotate-180 bg-sage-200 text-sage-900'
                                                            : 'text-charcoal-500 bg-cream-200/60'
                                                    "
                                                >
                                                    <svg
                                                        class="h-4 w-4"
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
                                                </span>
                                            </div>
                                        </button>

                                        <!-- Direct Link for categories without children -->
                                        <Link
                                            v-else
                                            :href="categoryShow.url(cat.slug)"
                                            class="flex w-full items-center justify-between rounded-xl px-3.5 py-3 text-sm font-medium text-charcoal-800 transition-colors hover:bg-cream-100 hover:text-sage-800"
                                            @click="close"
                                        >
                                            <span>{{ cat.name }}</span>
                                            <svg
                                                class="text-charcoal-400 h-4 w-4"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="1.5"
                                                    d="M9 5l7 7-7 7"
                                                />
                                            </svg>
                                        </Link>

                                        <!-- Children subcategories accordion -->
                                        <div
                                            v-if="
                                                cat.children &&
                                                cat.children.length > 0 &&
                                                expandedCategories[cat.id]
                                            "
                                            class="space-y-3 p-3 pt-1"
                                        >
                                            <!-- Prominent View All Category Link -->
                                            <Link
                                                :href="
                                                    categoryShow.url(cat.slug)
                                                "
                                                class="flex items-center justify-between rounded-lg bg-sage-800 px-3 py-2 text-xs font-semibold text-cream-50 shadow-2xs transition-colors hover:bg-sage-900"
                                                @click="close"
                                            >
                                                <span
                                                    >Explore All
                                                    {{ cat.name }}</span
                                                >
                                                <span
                                                    class="font-bold text-cream-200"
                                                    >&rarr;</span
                                                >
                                            </Link>

                                            <!-- Subcategories (2-column chips for longer lists, vertical list for compact categories) -->
                                            <div
                                                v-if="cat.children.length > 6"
                                                class="grid grid-cols-2 gap-1.5 pt-1"
                                            >
                                                <Link
                                                    v-for="child in cat.children"
                                                    :key="child.id"
                                                    :href="
                                                        categoryShow.url(
                                                            child.slug,
                                                        )
                                                    "
                                                    class="hover:border-sage-400 truncate rounded-lg border border-cream-200/90 bg-white/90 px-2.5 py-2 text-xs text-charcoal-700 shadow-2xs transition-colors hover:bg-white hover:text-sage-900"
                                                    :title="child.name"
                                                    @click="close"
                                                >
                                                    {{ child.name }}
                                                </Link>
                                            </div>
                                            <div v-else class="space-y-1 pt-1">
                                                <Link
                                                    v-for="child in cat.children"
                                                    :key="child.id"
                                                    :href="
                                                        categoryShow.url(
                                                            child.slug,
                                                        )
                                                    "
                                                    class="flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs text-charcoal-700 transition-colors hover:bg-cream-100 hover:text-sage-900"
                                                    @click="close"
                                                >
                                                    <span>{{
                                                        child.name
                                                    }}</span>
                                                    <span
                                                        class="text-charcoal-400"
                                                        >&rarr;</span
                                                    >
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                </nav>
                            </div>

                            <!-- Favourites & Cart Quick Links -->
                            <div
                                class="mt-6 space-y-2 border-t border-cream-200/80 pt-5"
                            >
                                <Link
                                    :href="
                                        props.user
                                            ? accountRituals.url()
                                            : favouritesIndex.url()
                                    "
                                    class="flex items-center justify-between rounded-xl border border-cream-200 px-3.5 py-2.5 text-xs font-medium text-charcoal-800 transition-colors hover:border-sage-300 hover:bg-white"
                                    :class="
                                        $page.url.startsWith(
                                            favouritesIndex.url(),
                                        ) ||
                                        $page.url.startsWith(
                                            accountRituals.url(),
                                        )
                                            ? 'border-sage-300 bg-cream-100 font-semibold text-sage-900'
                                            : 'bg-white/70'
                                    "
                                    @click="close"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <svg
                                            class="h-4 w-4 text-sage-600"
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
                                        <span>Wishlist & Favourites</span>
                                    </div>
                                    <span
                                        class="rounded-full bg-sage-100 px-2 py-0.5 text-[11px] font-semibold text-sage-800"
                                    >
                                        {{ favouritesCount }}
                                    </span>
                                </Link>

                                <Link
                                    :href="cartIndex.url()"
                                    class="flex items-center justify-between rounded-xl border border-cream-200 bg-white/70 px-3.5 py-2.5 text-xs font-medium text-charcoal-800 transition-colors hover:border-sage-300 hover:bg-white"
                                    @click="close"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <svg
                                            class="h-4 w-4 text-sage-600"
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
                                        <span>Shopping Cart</span>
                                    </div>
                                    <span
                                        class="rounded-full bg-sage-800 px-2.5 py-0.5 text-[11px] font-semibold text-cream-50"
                                    >
                                        {{ cartCount }}
                                    </span>
                                </Link>
                            </div>
                        </div>

                        <!-- Footer / User Account Section -->
                        <div class="border-t border-cream-200 bg-white/80 p-5">
                            <template v-if="props.user">
                                <div class="mb-3 flex items-center gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-sage-800 text-xs font-semibold text-cream-50"
                                    >
                                        {{
                                            props.user.name
                                                .charAt(0)
                                                .toUpperCase()
                                        }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="truncate text-xs font-medium text-charcoal-900"
                                        >
                                            {{ props.user.name }}
                                        </p>
                                        <p
                                            class="text-charcoal-500 truncate text-[11px]"
                                        >
                                            {{ props.user.email }}
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <Link
                                        :href="profile.url()"
                                        class="flex items-center justify-center gap-1.5 rounded-lg border border-cream-200 bg-cream-50 py-2 font-medium text-charcoal-800 transition-colors hover:bg-cream-100 hover:text-sage-800"
                                        @click="close"
                                    >
                                        <svg
                                            class="h-3.5 w-3.5 text-sage-600"
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
                                        <span>Profile</span>
                                    </Link>

                                    <Link
                                        :href="ordersIndex.url()"
                                        class="flex items-center justify-center gap-1.5 rounded-lg border border-cream-200 bg-cream-50 py-2 font-medium text-charcoal-800 transition-colors hover:bg-cream-100 hover:text-sage-800"
                                        @click="close"
                                    >
                                        <svg
                                            class="h-3.5 w-3.5 text-sage-600"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.5"
                                                d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"
                                            />
                                        </svg>
                                        <span>Orders</span>
                                    </Link>
                                </div>

                                <div class="mt-2.5">
                                    <Link
                                        :href="logout.url()"
                                        method="post"
                                        as="button"
                                        class="flex w-full items-center justify-center gap-1.5 rounded-lg py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-50 hover:text-red-700"
                                        @click="close"
                                    >
                                        <svg
                                            class="h-3.5 w-3.5"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.5"
                                                d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"
                                            />
                                        </svg>
                                        <span>Sign Out</span>
                                    </Link>
                                </div>
                            </template>

                            <template v-else>
                                <p
                                    class="text-xs font-medium text-charcoal-700"
                                >
                                    Client Sanctuary
                                </p>
                                <p class="text-charcoal-500 mt-0.5 text-[11px]">
                                    Sign in to track orders & save botanical
                                    favourites.
                                </p>
                                <div class="mt-3 flex gap-2">
                                    <Link
                                        :href="login.url()"
                                        class="flex-1 rounded-full bg-sage-800 py-2 text-center text-xs font-semibold tracking-wider text-cream-50 uppercase shadow-xs transition-colors hover:bg-sage-900"
                                        @click="close"
                                    >
                                        Sign In
                                    </Link>
                                    <Link
                                        :href="register.url()"
                                        class="flex-1 rounded-full border border-sage-700 py-2 text-center text-xs font-semibold tracking-wider text-sage-800 uppercase transition-colors hover:bg-sage-50"
                                        @click="close"
                                    >
                                        Register
                                    </Link>
                                </div>
                            </template>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </Teleport>
</template>
