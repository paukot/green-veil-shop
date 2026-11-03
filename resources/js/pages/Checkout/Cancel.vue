<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { formatCurrency } from '@/lib/utils';
import type { CheckoutCancelPageProps } from '@/types/storefront';
import { retry as checkoutRetry } from '@/actions/App/Http/Controllers/CheckoutResultController';
import { index as cartIndex } from '@/routes/cart';

defineOptions({ layout: StorefrontLayout });

defineProps<CheckoutCancelPageProps>();
</script>

<template>
    <Head :title="`Order ${order.number} Cancelled | Green Veil`" />

    <section class="mx-auto max-w-2xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
        <div
            class="rounded-3xl border border-cream-200 bg-white p-8 text-center shadow-xs sm:p-12"
        >
            <div
                class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-amber-50 text-amber-700"
            >
                <svg
                    class="h-8 w-8"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"
                    />
                </svg>
            </div>

            <p
                class="text-xs font-semibold tracking-widest text-amber-800 uppercase"
            >
                Payment Incomplete
            </p>
            <h1 class="mt-2 font-serif text-3xl text-charcoal-900">
                Your Order Was Not Finalized
            </h1>
            <p
                class="text-charcoal-600 mt-3 text-xs leading-relaxed sm:text-sm"
            >
                No payment was captured for order
                <span class="font-mono font-semibold">{{ order.number }}</span>
                ({{ formatCurrency(order.total) }}). Your reserved items are
                still held if you would like to complete your order or choose a
                different payment option.
            </p>

            <div
                class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row"
            >
                <Link
                    :href="checkoutRetry.url(order.public_id)"
                    method="post"
                    as="button"
                    class="w-full rounded-full bg-sage-800 px-8 py-3.5 text-xs font-semibold tracking-wider text-cream-50 uppercase shadow-sm transition-all hover:bg-sage-900 hover:shadow-md sm:w-auto"
                >
                    Try Payment Again
                </Link>
                <Link
                    :href="cartIndex.url()"
                    class="w-full rounded-full border border-cream-300 bg-white px-8 py-3.5 text-xs font-semibold tracking-wider text-charcoal-700 uppercase transition-all hover:bg-cream-50 sm:w-auto"
                >
                    Return to Bag
                </Link>
            </div>
        </div>
    </section>
</template>
