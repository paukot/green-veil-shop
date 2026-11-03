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

const page = usePage<SharedProps>();
const user = computed(() => page.props.auth?.user);
const navigation = computed(() => page.props.navigation || []);
const currentYear = new Date().getFullYear();
</script>

<template>
    <footer class="mt-18 border-t border-cream-200 bg-cream-100 pt-8 pb-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-10 md:grid-cols-4">
                <!-- Brand Ethos -->
                <div class="space-y-3 md:col-span-1">
                    <span class="block font-serif text-2xl text-sage-900"
                        >Green Veil</span
                    >
                    <p class="text-xs leading-relaxed text-charcoal-700">
                        Thoughtfully formulated botanical skincare and
                        cosmetics. Rooted in herbal tradition, refined by gentle
                        science.
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
                                prefetch
                                class="transition-colors hover:text-sage-700"
                            >
                                All Products
                            </Link>
                        </li>
                        <li v-for="cat in navigation" :key="cat.id">
                            <Link
                                :href="categoryShow.url(cat.slug)"
                                prefetch
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
                            >
                                Shopping Cart
                            </Link>
                        </li>
                        <li>
                            <Link
                                :href="favouritesIndex.url()"
                                class="transition-colors hover:text-sage-700"
                            >
                                Favourites
                            </Link>
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

                <!-- Botanical Promise -->
                <div class="space-y-3">
                    <h2
                        class="text-xs font-semibold tracking-wider text-sage-800 uppercase"
                    >
                        Our Promise
                    </h2>
                    <p class="text-xs leading-relaxed text-charcoal-700">
                        100% cruelty-free, recyclable glass packaging, and clean
                        botanical actives with total transparency.
                    </p>
                </div>
            </div>

            <div
                class="text-charcoal-500 mt-12 flex flex-col items-center justify-between gap-4 border-t border-cream-200 p-2 text-xs sm:flex-row"
            >
                <p>
                    &copy; {{ currentYear }} Green Veil Cosmetics. All rights
                    reserved.
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
</template>
