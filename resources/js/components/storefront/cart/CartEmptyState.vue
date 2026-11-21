<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { index as productsIndex } from '@/routes/products';

import { ShoppingBagIcon } from '@lucide/vue';
</script>

<template>
    <div
        class="mx-auto max-w-md rounded-3xl border border-cream-200 bg-white p-8 py-20 text-center shadow-xs sm:p-12"
    >
        <div
            class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-sage-50 text-sage-700"
        >
            <ShoppingBagIcon
                class="h-10 w-10"
                :stroke-width="1.25"
                aria-hidden="true"
            />
        </div>
        <h2 class="mb-2 font-serif text-2xl text-charcoal-900">
            Your shopping bag is empty
        </h2>
        <p
            class="text-charcoal-600 mb-8 text-xs leading-relaxed font-light sm:text-sm"
        >
            Your bag is waiting for pure botanical cleansers, soothing mists,
            and restorative skincare rituals.
        </p>
        <Link
            :href="productsIndex.url()"
            class="inline-block rounded-full bg-sage-800 px-8 py-3.5 text-xs font-semibold tracking-wider text-cream-50 uppercase shadow-sm transition-all hover:bg-sage-900 hover:shadow-md"
        >
            Explore Apothecary Collections
        </Link>
    </div>
</template>
