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
import {
    ChevronDownIcon,
    ChevronRightIcon,
    HeartIcon,
    HouseIcon,
    LogOutIcon,
    PackageIcon,
    ShoppingBagIcon,
    UserIcon,
    XIcon,
} from '@lucide/vue';

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
                                <XIcon
                                    class="h-5 w-5"
                                    :stroke-width="1.5"
                                    aria-hidden="true"
                                />
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
                                    <HouseIcon
                                        class="h-4 w-4 text-sage-600"
                                        :stroke-width="1.5"
                                        aria-hidden="true"
                                    />
                                    <span>Home</span>
                                </Link>

                                <Link
                                    :href="productsIndex.url()"
                                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-charcoal-800 transition-colors hover:bg-cream-100 hover:text-sage-800"
                                    @click="close"
                                >
                                    <PackageIcon
                                        class="h-4 w-4 text-sage-600"
                                        :stroke-width="1.5"
                                        aria-hidden="true"
                                    />
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
                                                    <ChevronDownIcon
                                                        class="h-4 w-4"
                                                        aria-hidden="true"
                                                    />
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
                                            <ChevronRightIcon
                                                class="text-charcoal-400 h-4 w-4"
                                                :stroke-width="1.5"
                                                aria-hidden="true"
                                            />
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
                                        <HeartIcon
                                            class="h-4 w-4 text-sage-600"
                                            :stroke-width="1.5"
                                            aria-hidden="true"
                                        />
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
                                        <ShoppingBagIcon
                                            class="h-4 w-4 text-sage-600"
                                            :stroke-width="1.5"
                                            aria-hidden="true"
                                        />
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
                                        <UserIcon
                                            class="h-3.5 w-3.5 text-sage-600"
                                            :stroke-width="1.5"
                                            aria-hidden="true"
                                        />
                                        <span>Profile</span>
                                    </Link>

                                    <Link
                                        :href="ordersIndex.url()"
                                        class="flex items-center justify-center gap-1.5 rounded-lg border border-cream-200 bg-cream-50 py-2 font-medium text-charcoal-800 transition-colors hover:bg-cream-100 hover:text-sage-800"
                                        @click="close"
                                    >
                                        <PackageIcon
                                            class="h-3.5 w-3.5 text-sage-600"
                                            :stroke-width="1.5"
                                            aria-hidden="true"
                                        />
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
                                        <LogOutIcon
                                            class="h-3.5 w-3.5"
                                            :stroke-width="1.5"
                                            aria-hidden="true"
                                        />
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
