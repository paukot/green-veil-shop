<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { index as productsIndex } from '@/routes/products';
</script>

<template>
    <div
        class="mx-auto max-w-md rounded-3xl border border-cream-200 bg-white p-8 py-20 text-center shadow-xs sm:p-12"
    >
        <div
            class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-sage-50 text-sage-700"
        >
            <svg
                class="h-10 w-10"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.25"
                    d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"
                />
            </svg>
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
