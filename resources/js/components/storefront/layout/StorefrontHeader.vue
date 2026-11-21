<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { cn } from '@/lib/utils';
import MegaMenuDropdown from '@/components/storefront/layout/MegaMenuDropdown.vue';
import LiveSearchAutocomplete from '@/components/storefront/layout/LiveSearchAutocomplete.vue';
import type { SharedProps } from '@/types/storefront';
import { home, login, logout } from '@/routes';
import { index as productsIndex } from '@/routes/products';
import { show as categoryShow } from '@/actions/App/Http/Controllers/CategoryController';
import { index as favouritesIndex } from '@/routes/favourites';
import { profile, rituals as accountRituals } from '@/routes/account';

import { useCart } from '@/composables/useCart';
import { useFavourites } from '@/composables/useFavourites';
import {
    ChevronDownIcon,
    HeartIcon,
    LogOutIcon,
    MenuIcon,
    SearchIcon,
    ShoppingBagIcon,
    UserIcon,
    XIcon,
} from '@lucide/vue';
import Button from '@/components/ui/Button.vue';
import Container from '@/components/ui/Container.vue';
import IconButton from '@/components/ui/IconButton.vue';

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

const isFavouritesPage = computed(
    () =>
        page.url.startsWith(favouritesIndex.url()) ||
        page.url.startsWith(accountRituals.url()),
);
</script>

<template>
    <aside
        aria-label="Announcement"
        :class="
            isDemoMode
                ? 'bg-clay-800 text-clay-50'
                : 'bg-sage-950 text-cream-100'
        "
    >
        <Container
            class="flex min-h-8 items-center justify-center gap-2 py-2 text-center text-xs"
        >
            <template v-if="isDemoMode">
                <span
                    class="inline-flex h-6 shrink-0 items-center rounded-full bg-clay-950/40 px-2 font-semibold tracking-wider uppercase"
                >
                    Demo
                </span>
                <span>{{ demoNotice }}</span>
            </template>
            <span v-else>
                Botanical formulas, conscious ingredients · Complimentary
                delivery over €50
            </span>
        </Container>
    </aside>

    <header
        class="sticky top-0 z-40 border-b border-border bg-surface/95 backdrop-blur-md"
    >
        <Container
            class="grid h-16 grid-cols-[1fr_auto_1fr] items-center gap-2 lg:h-24"
        >
            <!-- Left: menu and search -->
            <div class="flex items-center gap-2">
                <IconButton
                    :label="mobileMenuOpen ? 'Close menu' : 'Open menu'"
                    class="-ml-3 lg:hidden"
                    :aria-expanded="mobileMenuOpen"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                >
                    <XIcon
                        v-if="mobileMenuOpen"
                        class="size-6"
                        aria-hidden="true"
                    />
                    <MenuIcon v-else class="size-6" aria-hidden="true" />
                </IconButton>
                <IconButton
                    label="Search"
                    class="lg:hidden"
                    :aria-expanded="searchOpen"
                    @click="searchOpen = !searchOpen"
                >
                    <SearchIcon class="size-6" aria-hidden="true" />
                </IconButton>
                <Button
                    variant="ghost"
                    size="sm"
                    class="-ml-4 hidden lg:inline-flex"
                    :aria-expanded="searchOpen"
                    @click="searchOpen = !searchOpen"
                >
                    <template #icon>
                        <SearchIcon class="size-4" aria-hidden="true" />
                    </template>
                    Search
                </Button>
            </div>

            <!-- Centre: wordmark -->
            <Link
                :href="home.url()"
                prefetch
                class="flex flex-col items-center gap-1 text-center"
                aria-label="Green Veil home"
            >
                <span
                    class="font-serif text-2xl leading-none tracking-tight text-sage-950 lg:text-4xl"
                >
                    Green Veil
                </span>
                <span
                    class="hidden text-xs tracking-widest text-ink-subtle uppercase lg:block"
                >
                    Botanical Apothecary
                </span>
            </Link>

            <!-- Right: favourites, bag, account -->
            <div class="flex items-center justify-end gap-2">
                <IconButton
                    label="Favourites"
                    :href="user ? accountRituals.url() : favouritesIndex.url()"
                    :badge="favouritesCount"
                    :class="
                        cn(
                            'hidden sm:inline-flex',
                            isFavouritesPage && 'bg-surface-sunken',
                        )
                    "
                >
                    <HeartIcon class="size-6" aria-hidden="true" />
                </IconButton>

                <IconButton
                    label="Shopping bag"
                    :badge="cartCount"
                    @click="cart.openDrawer()"
                >
                    <ShoppingBagIcon class="size-6" aria-hidden="true" />
                </IconButton>

                <div class="group relative -mr-3">
                    <IconButton
                        :label="user ? 'Your account' : 'Sign in'"
                        :href="user ? profile.url() : login.url()"
                    >
                        <UserIcon class="size-6" aria-hidden="true" />
                    </IconButton>

                    <div
                        v-if="user"
                        class="invisible absolute top-full right-0 z-50 w-64 pt-2 opacity-0 transition-[opacity,visibility] duration-150 group-focus-within:visible group-focus-within:opacity-100 group-hover:visible group-hover:opacity-100"
                    >
                        <div
                            class="flex flex-col gap-1 rounded-lg border border-border bg-surface-raised p-2 shadow-lg"
                        >
                            <div
                                class="flex flex-col gap-1 border-b border-border px-3 pt-2 pb-3"
                            >
                                <p
                                    class="truncate text-sm font-medium text-ink"
                                >
                                    {{ user.name }}
                                </p>
                                <p class="truncate text-xs text-ink-subtle">
                                    {{ user.email }}
                                </p>
                            </div>
                            <Link
                                :href="profile.url()"
                                class="flex h-8 items-center gap-2 rounded-lg px-3 text-sm text-ink transition-colors hover:bg-surface-sunken"
                            >
                                <UserIcon class="size-4" aria-hidden="true" />
                                My account
                            </Link>
                            <Link
                                :href="logout.url()"
                                method="post"
                                as="button"
                                class="flex h-8 w-full items-center gap-2 rounded-lg px-3 text-left text-sm text-danger-600 transition-colors hover:bg-danger-50"
                            >
                                <LogOutIcon class="size-4" aria-hidden="true" />
                                Sign out
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </Container>

        <!-- Search panel: one instance for every screen size -->
        <div v-if="searchOpen" class="border-t border-border py-4">
            <Container size="narrow">
                <LiveSearchAutocomplete
                    placeholder="Search botanicals, perfumes, skincare…"
                    :autofocus="true"
                    @close="searchOpen = false"
                />
            </Container>
        </div>

        <!-- Department navigation (desktop) -->
        <nav
            class="relative hidden border-t border-border lg:block"
            aria-label="Primary"
        >
            <Container>
                <ul
                    class="flex h-12 items-stretch justify-center gap-8 text-sm"
                >
                    <li class="flex">
                        <Link
                            :href="productsIndex.url()"
                            prefetch
                            class="flex items-center border-b-2 border-transparent text-ink transition-colors hover:border-ink"
                        >
                            All products
                        </Link>
                    </li>
                    <li
                        v-for="cat in navigation"
                        :key="cat.id"
                        class="group flex"
                    >
                        <Link
                            :href="categoryShow.url(cat.slug)"
                            prefetch
                            class="flex items-center gap-2 border-b-2 border-transparent text-ink transition-colors group-hover:border-ink"
                        >
                            {{ cat.name }}
                            <ChevronDownIcon
                                v-if="cat.children && cat.children.length > 0"
                                class="size-4 text-ink-subtle transition-transform duration-200 group-hover:rotate-180"
                                aria-hidden="true"
                            />
                        </Link>

                        <MegaMenuDropdown
                            v-if="cat.children && cat.children.length > 0"
                            :category="cat"
                        />
                    </li>
                </ul>
            </Container>
        </nav>
    </header>
</template>
