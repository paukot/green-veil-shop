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
        :class="inline ? '' : 'border-b border-border'"
    >
        <div :class="inline ? '' : 'mx-auto max-w-7xl px-4 sm:px-6 lg:px-8'">
            <ol
                :class="[
                    'scrollbar-none flex items-center gap-2 overflow-x-auto text-xs whitespace-nowrap text-ink-subtle',
                    inline ? '' : 'h-12',
                ]"
            >
                <li
                    v-for="(crumb, idx) in items"
                    :key="crumb.url || crumb.name"
                    class="flex items-center gap-2"
                >
                    <Link
                        v-if="idx < items.length - 1"
                        :href="crumb.url"
                        class="transition-colors hover:text-ink"
                    >
                        {{ crumb.name }}
                    </Link>
                    <span
                        v-else
                        class="max-w-64 truncate font-medium text-ink sm:max-w-none"
                        aria-current="page"
                    >
                        {{ crumb.name }}
                    </span>
                    <ChevronRightIcon
                        v-if="idx < items.length - 1"
                        class="size-3 shrink-0"
                        aria-hidden="true"
                    />
                </li>
            </ol>
        </div>
    </nav>
</template>
