<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { home } from '@/routes';
import { index as productsIndex } from '@/routes/products';

defineOptions({
    layout: StorefrontLayout,
});

const props = defineProps<{
    status: number;
    message?: string;
}>();

const title = computed(() => {
    switch (props.status) {
        case 404:
            return 'Page Not Found';
        case 403:
            return 'Access Restricted';
        case 503:
            return 'Sanctuary Under Maintenance';
        default:
            return 'Apothecary Error';
    }
});

const description = computed(() => {
    if (props.message) return props.message;
    switch (props.status) {
        case 404:
            return 'The botanical formulation or page you are looking for may have been moved or is no longer available.';
        case 403:
            return 'You do not have permission to access this designated area of our botanical sanctuary.';
        case 503:
            return 'We are currently refining our apothecary store. Please visit us again shortly.';
        default:
            return 'An unexpected issue occurred while preparing your request. Our apothecary team has been notified.';
    }
});
</script>

<template>
    <Head :title="`${status} — ${title} | Green Veil`" />

    <div
        class="mx-auto max-w-xl px-4 py-16 text-center sm:px-6 sm:py-24 lg:px-8"
    >
        <div
            class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full border border-cream-200 bg-cream-100 text-sage-700"
        >
            <svg
                v-if="status === 404"
                class="h-10 w-10"
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
            <svg
                v-else-if="status === 403"
                class="h-10 w-10"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"
                />
            </svg>
            <svg
                v-else
                class="h-10 w-10"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                />
            </svg>
        </div>

        <p
            class="mb-2 text-xs font-semibold tracking-widest text-sage-600 uppercase"
        >
            Error {{ status }}
        </p>
        <h1 class="mb-4 font-serif text-3xl text-sage-900 sm:text-4xl">
            {{ title }}
        </h1>
        <p class="text-charcoal-600 mb-8 text-sm leading-relaxed">
            {{ description }}
        </p>

        <div
            class="flex flex-col items-center justify-center gap-4 sm:flex-row"
        >
            <Link
                :href="home.url()"
                class="w-full rounded-xl bg-sage-800 px-6 py-3 text-xs font-medium tracking-wider text-cream-50 uppercase shadow-xs transition-colors hover:bg-sage-900 sm:w-auto"
            >
                Return to Sanctuary Home
            </Link>
            <Link
                :href="productsIndex.url()"
                class="w-full rounded-xl border border-cream-300 bg-white px-6 py-3 text-xs font-medium tracking-wider text-charcoal-800 uppercase transition-colors hover:bg-cream-100 sm:w-auto"
            >
                Explore All Botanicals
            </Link>
        </div>
    </div>
</template>
