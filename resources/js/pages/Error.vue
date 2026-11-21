<script setup lang="ts">
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { home } from '@/routes';
import { index as productsIndex } from '@/routes/products';
import Button from '@/components/ui/Button.vue';
import Container from '@/components/ui/Container.vue';

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
    <Head :title="`${status} — ${title}`" />

    <Container size="narrow" class="py-16 lg:py-24">
        <div class="flex flex-col items-center gap-6 text-center">
            <p
                class="font-serif text-7xl leading-none tracking-tight text-clay-600 lg:text-display"
                aria-hidden="true"
            >
                {{ status }}
            </p>
            <div class="flex flex-col gap-3">
                <h1 class="font-serif text-4xl tracking-tight text-ink">
                    {{ title }}
                </h1>
                <p class="max-w-md text-base text-ink-muted">
                    {{ description }}
                </p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                <Button :href="home.url()" size="lg">Back to home</Button>
                <Button :href="productsIndex.url()" variant="outline" size="lg">
                    Browse products
                </Button>
            </div>
        </div>
    </Container>
</template>
