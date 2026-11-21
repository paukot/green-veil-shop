<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import type { SharedProps } from '@/types/storefront';
import { index as productsIndex } from '@/routes/products';
import { show as categoryShow } from '@/actions/App/Http/Controllers/CategoryController';
import { index as cartIndex } from '@/routes/cart';
import { index as favouritesIndex } from '@/routes/favourites';
import { profile } from '@/routes/account';
import { login } from '@/routes';
import { LeafIcon, RabbitIcon, RecycleIcon } from '@lucide/vue';
import Container from '@/components/ui/Container.vue';

const page = usePage<SharedProps>();
const user = computed(() => page.props.auth?.user);
const navigation = computed(() => page.props.navigation || []);
const currentYear = new Date().getFullYear();

const promises = [
    { icon: LeafIcon, label: 'Pure botanical actives' },
    { icon: RabbitIcon, label: 'Never tested on animals' },
    { icon: RecycleIcon, label: 'Recyclable glass & refills' },
];
</script>

<template>
    <footer class="mt-16 bg-sage-950 text-cream-100">
        <Container class="flex flex-col gap-16 py-16 lg:py-24">
            <div class="grid gap-12 lg:grid-cols-12">
                <div class="flex flex-col gap-6 lg:col-span-5">
                    <p class="font-serif text-4xl text-cream-50">Green Veil</p>
                    <p class="max-w-sm text-sm leading-6 text-sage-200">
                        Thoughtfully formulated botanical skincare and
                        cosmetics. Rooted in herbal tradition, refined by gentle
                        science.
                    </p>
                    <ul class="flex flex-col gap-3 text-sm text-sage-100">
                        <li
                            v-for="promise in promises"
                            :key="promise.label"
                            class="flex items-center gap-3"
                        >
                            <span
                                class="flex size-8 items-center justify-center rounded-full bg-sage-900 text-sage-300"
                            >
                                <component
                                    :is="promise.icon"
                                    class="size-4"
                                    aria-hidden="true"
                                />
                            </span>
                            {{ promise.label }}
                        </li>
                    </ul>
                </div>

                <nav
                    class="grid gap-8 sm:grid-cols-2 lg:col-span-6 lg:col-start-7"
                    aria-label="Footer"
                >
                    <div class="flex flex-col gap-4">
                        <h2
                            class="text-xs font-semibold tracking-widest text-sage-300 uppercase"
                        >
                            Shop
                        </h2>
                        <ul class="flex flex-col gap-3 text-sm">
                            <li>
                                <Link
                                    :href="productsIndex.url()"
                                    prefetch
                                    class="text-cream-100 transition-colors hover:text-white"
                                >
                                    All products
                                </Link>
                            </li>
                            <li v-for="cat in navigation" :key="cat.id">
                                <Link
                                    :href="categoryShow.url(cat.slug)"
                                    prefetch
                                    class="text-cream-100 transition-colors hover:text-white"
                                >
                                    {{ cat.name }}
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <div class="flex flex-col gap-4">
                        <h2
                            class="text-xs font-semibold tracking-widest text-sage-300 uppercase"
                        >
                            Customer care
                        </h2>
                        <ul class="flex flex-col gap-3 text-sm">
                            <li>
                                <Link
                                    :href="cartIndex.url()"
                                    class="text-cream-100 transition-colors hover:text-white"
                                >
                                    Shopping bag
                                </Link>
                            </li>
                            <li>
                                <Link
                                    :href="favouritesIndex.url()"
                                    class="text-cream-100 transition-colors hover:text-white"
                                >
                                    Favourites
                                </Link>
                            </li>
                            <li>
                                <Link
                                    :href="user ? profile.url() : login.url()"
                                    class="text-cream-100 transition-colors hover:text-white"
                                >
                                    {{ user ? 'My account' : 'Sign in' }}
                                </Link>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>

            <div
                class="flex flex-col gap-4 border-t border-sage-800 pt-8 text-xs text-sage-300 sm:flex-row sm:items-center sm:justify-between"
            >
                <p>
                    © {{ currentYear }} Green Veil Cosmetics. All rights
                    reserved.
                </p>
                <p>Pure botanical care · Cruelty free · Sustainably sourced</p>
            </div>
        </Container>
    </footer>
</template>
