<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types/storefront';
import { ChevronRightIcon } from '@lucide/vue';

withDefaults(
    defineProps<{
        items: BreadcrumbItem[];
        inline?: boolean;
    }>(),
    {
        inline: false,
    },
);
</script>

<template>
    <nav
        v-if="items && items.length > 0"
        aria-label="Breadcrumb"
        :class="inline ? '' : 'border-b border-cream-200 bg-cream-100/60'"
    >
        <div
            :class="inline ? '' : 'mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8'"
        >
            <ol
                class="text-charcoal-600 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-xs"
            >
                <li
                    v-for="(crumb, idx) in items"
                    :key="crumb.url || crumb.name"
                    class="flex items-center"
                >
                    <Link
                        v-if="idx < items.length - 1"
                        :href="crumb.url"
                        class="max-w-[140px] truncate transition-colors hover:text-sage-800 sm:max-w-none"
                    >
                        {{ crumb.name }}
                    </Link>
                    <span
                        v-else
                        class="max-w-[160px] truncate font-semibold text-charcoal-900 sm:max-w-none"
                        aria-current="page"
                    >
                        {{ crumb.name }}
                    </span>
                    <ChevronRightIcon
                        v-if="idx < items.length - 1"
                        class="text-charcoal-400 mx-1 h-3 w-3 shrink-0"
                        aria-hidden="true"
                    />
                </li>
            </ol>
        </div>
    </nav>
</template>
