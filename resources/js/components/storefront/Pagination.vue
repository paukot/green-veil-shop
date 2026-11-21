<script setup lang="ts">
import { ChevronLeftIcon, ChevronRightIcon } from '@lucide/vue';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/vue3';
import type { PaginationLink } from '@/types/storefront';

const props = defineProps<{
    links: PaginationLink[];
    /**
     * Props to reload when changing page. Paging only needs the list itself, so a partial
     * reload skips everything else on the page (facets, categories, shared data).
     */
    only?: string[];
    /** Element id to bring into view once the new page has loaded. */
    scrollTarget?: string;
}>();

function scrollToTarget() {
    if (!props.scrollTarget) return;

    document.getElementById(props.scrollTarget)?.scrollIntoView({
        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches
            ? 'auto'
            : 'smooth',
        block: 'start',
    });
}

function linkBindings(url: string | null) {
    if (!url) return {};

    return {
        href: url,
        only: props.only,
        preserveState: props.only ? true : undefined,
        preserveScroll: true,
        prefetch: true,
        onSuccess: scrollToTarget,
    };
}

function formatLabel(label: string): string {
    return label.replace('&laquo;', '«').replace('&raquo;', '»');
}
</script>

<template>
    <nav
        v-if="links.length > 3"
        aria-label="Pagination"
        class="flex items-center justify-center gap-2"
    >
        <template v-for="(link, index) in links" :key="index">
            <component
                :is="link.url ? Link : 'span'"
                v-bind="linkBindings(link.url)"
                :aria-current="link.active ? 'page' : undefined"
                :aria-disabled="link.url ? undefined : true"
                :aria-label="
                    index === 0
                        ? 'Previous page'
                        : index === links.length - 1
                          ? 'Next page'
                          : undefined
                "
                :class="
                    cn(
                        'inline-flex size-12 items-center justify-center rounded-full text-sm font-medium transition-colors',
                        index !== 0 &&
                            index !== links.length - 1 &&
                            !link.active &&
                            'hidden sm:inline-flex',
                        link.active
                            ? 'bg-sage-900 text-cream-50'
                            : link.url
                              ? 'text-ink hover:bg-surface-sunken'
                              : 'cursor-not-allowed text-ink-subtle opacity-50',
                        (index === 0 || index === links.length - 1) &&
                            'border border-border-strong',
                    )
                "
            >
                <ChevronLeftIcon
                    v-if="index === 0"
                    class="size-4"
                    aria-hidden="true"
                />
                <ChevronRightIcon
                    v-else-if="index === links.length - 1"
                    class="size-4"
                    aria-hidden="true"
                />
                <template v-else>{{ formatLabel(link.label) }}</template>
            </component>
        </template>
    </nav>
</template>
