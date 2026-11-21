<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { home } from '@/routes';
import { index as productsIndex } from '@/routes/products';
import { CircleAlertIcon, LockIcon, TriangleAlertIcon } from '@lucide/vue';

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
            <CircleAlertIcon
                v-if="status === 404"
                class="h-10 w-10"
                :stroke-width="1.5"
                aria-hidden="true"
            />
            <LockIcon
                v-else-if="status === 403"
                class="h-10 w-10"
                :stroke-width="1.5"
                aria-hidden="true"
            />
            <TriangleAlertIcon
                v-else
                class="h-10 w-10"
                :stroke-width="1.5"
                aria-hidden="true"
            />
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
