<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import {
    ChevronDownIcon,
    HeartIcon,
    LogOutIcon,
    PackageIcon,
    ShoppingBagIcon,
    UserIcon,
} from '@lucide/vue';
import { SearchInput } from '@/components/form';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Drawer from '@/components/ui/Drawer.vue';
import IconButton from '@/components/ui/IconButton.vue';
import type { CategoryNavigationItem, CustomerUser } from '@/types/storefront';
import { home, login, logout, register } from '@/routes';
import { index as productsIndex } from '@/routes/products';
import { show as categoryShow } from '@/actions/App/Http/Controllers/CategoryController';
import { index as cartIndex } from '@/routes/cart';
import { index as favouritesIndex } from '@/routes/favourites';
import { profile, rituals as accountRituals } from '@/routes/account';
import { index as ordersIndex } from '@/routes/account/orders';
import { cn } from '@/lib/utils';

const open = defineModel<boolean>('open', { default: false });

defineProps<{
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

let removeNavigateListener: (() => void) | null = null;

onMounted(() => {
    removeNavigateListener = router.on('navigate', close);
});

onUnmounted(() => {
    removeNavigateListener?.();
});

const rowClass =
    'flex h-12 w-full items-center gap-3 px-6 text-base text-ink transition-colors hover:bg-surface-sunken';
</script>

<template>
    <Drawer
        :open="open"
        title="Menu"
        side="left"
        size="sm"
        tone="surface"
        @close="close"
    >
        <div class="flex flex-col gap-6 py-6">
            <form class="px-6" role="search" @submit.prevent="submitSearch">
                <SearchInput
                    v-model="searchQuery"
                    placeholder="Search products…"
                    aria-label="Search products"
                />
            </form>

            <nav aria-label="Mobile" class="flex flex-col">
                <Link :href="home.url()" :class="rowClass" @click="close">
                    Home
                </Link>
                <Link
                    :href="productsIndex.url()"
                    :class="rowClass"
                    @click="close"
                >
                    All products
                </Link>

                <div
                    v-for="cat in navigation"
                    :key="cat.id"
                    class="flex flex-col"
                >
                    <div class="flex items-center justify-between pr-4">
                        <Link
                            :href="categoryShow.url(cat.slug)"
                            class="flex h-12 flex-1 items-center px-6 font-serif text-xl text-ink"
                            @click="close"
                        >
                            {{ cat.name }}
                        </Link>
                        <IconButton
                            v-if="cat.children && cat.children.length > 0"
                            :label="`Show ${cat.name} categories`"
                            size="sm"
                            :aria-expanded="Boolean(expandedCategories[cat.id])"
                            @click="toggleCategory(cat.id)"
                        >
                            <ChevronDownIcon
                                :class="
                                    cn(
                                        'size-4 transition-transform',
                                        expandedCategories[cat.id] &&
                                            'rotate-180',
                                    )
                                "
                                aria-hidden="true"
                            />
                        </IconButton>
                    </div>
                    <ul
                        v-if="expandedCategories[cat.id] && cat.children"
                        class="flex flex-col pb-2"
                    >
                        <li v-for="child in cat.children" :key="child.id">
                            <Link
                                :href="categoryShow.url(child.slug)"
                                class="flex h-8 items-center px-12 text-sm text-ink-muted transition-colors hover:text-ink"
                                @click="close"
                            >
                                {{ child.name }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="flex flex-col border-t border-border pt-6">
                <Link
                    :href="user ? accountRituals.url() : favouritesIndex.url()"
                    :class="rowClass"
                    @click="close"
                >
                    <HeartIcon
                        class="size-6 text-rose-700"
                        aria-hidden="true"
                    />
                    Favourites
                    <Badge v-if="favouritesCount > 0" class="ml-auto">
                        {{ favouritesCount }}
                    </Badge>
                </Link>
                <Link :href="cartIndex.url()" :class="rowClass" @click="close">
                    <ShoppingBagIcon
                        class="size-6 text-rose-700"
                        aria-hidden="true"
                    />
                    Shopping bag
                    <Badge v-if="cartCount > 0" class="ml-auto">
                        {{ cartCount }}
                    </Badge>
                </Link>
                <template v-if="user">
                    <Link
                        :href="profile.url()"
                        :class="rowClass"
                        @click="close"
                    >
                        <UserIcon
                            class="size-6 text-rose-700"
                            aria-hidden="true"
                        />
                        My account
                    </Link>
                    <Link
                        :href="ordersIndex.url()"
                        :class="rowClass"
                        @click="close"
                    >
                        <PackageIcon
                            class="size-6 text-rose-700"
                            aria-hidden="true"
                        />
                        Orders
                    </Link>
                    <Link
                        :href="logout.url()"
                        method="post"
                        as="button"
                        :class="cn(rowClass, 'text-danger-600')"
                    >
                        <LogOutIcon class="size-6" aria-hidden="true" />
                        Sign out
                    </Link>
                </template>
            </div>
        </div>

        <template v-if="!user" #footer>
            <div class="grid grid-cols-2 gap-3">
                <Button :href="login.url()" variant="primary">Sign in</Button>
                <Button :href="register.url()" variant="outline">Join</Button>
            </div>
        </template>
    </Drawer>
</template>
