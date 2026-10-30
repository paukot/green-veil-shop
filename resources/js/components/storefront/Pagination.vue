<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { PaginationLink } from '@/types/storefront';

defineProps<{
    links: PaginationLink[];
}>();

function formatLabel(label: string): string {
    return label.replace('&laquo;', '«').replace('&raquo;', '»');
}
</script>

<template>
    <nav
        v-if="links.length > 3"
        aria-label="Pagination Navigation"
        class="my-10 flex items-center justify-center gap-1 sm:gap-2"
    >
        <template v-for="(link, index) in links" :key="index">
            <!-- Disabled / null URL link (e.g. Previous when on page 1) -->
            <span
                v-if="!link.url"
                class="text-charcoal-400 cursor-not-allowed rounded-md border border-cream-200 bg-cream-100 px-3.5 py-2 text-xs font-medium select-none"
            >
                {{ formatLabel(link.label) }}
            </span>

            <!-- Active / Clickable link -->
            <Link
                v-else
                :href="link.url"
                preserve-scroll
                :class="[
                    'rounded-md border px-3.5 py-2 text-xs font-medium transition-colors',
                    link.active
                        ? 'border-sage-700 bg-sage-700 font-semibold text-white'
                        : 'border-cream-200 bg-white text-charcoal-700 hover:bg-cream-100 hover:text-sage-800',
                ]"
                :aria-current="link.active ? 'page' : undefined"
            >
                {{ formatLabel(link.label) }}
            </Link>
        </template>
    </nav>
</template>
