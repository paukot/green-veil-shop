<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import type { CategoryNavigationItem } from '@/types/storefront';
import { show as categoryShow } from '@/actions/App/Http/Controllers/CategoryController';
import { getNavigationLayout } from '@/lib/navigation';

const props = defineProps<{
    category: CategoryNavigationItem;
}>();

const layout = computed(() => getNavigationLayout(props.category));
</script>

<template>
    <div
        v-if="category.children && category.children.length > 0"
        class="pointer-events-none absolute top-full z-50 pt-1.5 opacity-0 transition-all duration-150 ease-out group-hover:pointer-events-auto group-hover:opacity-100"
        :class="layout.containerWidthClass"
    >
        <div
            class="rounded-2xl border border-cream-200/90 bg-cream-50/98 p-5 shadow-2xl backdrop-blur-md"
            :class="layout.innerCardClass"
        >
            <!-- Mega Menu Header (for large departments) -->
            <div
                v-if="layout.isMegaMenu"
                class="mb-4 flex items-center justify-between border-b border-cream-200/80 pb-3"
            >
                <div class="flex items-center gap-2.5">
                    <h3 class="font-serif text-base font-medium text-sage-900">
                        {{ category.name }} Department
                    </h3>
                    <span
                        class="rounded-full bg-sage-100 px-2.5 py-0.5 text-[11px] font-semibold text-sage-800"
                    >
                        {{ category.children.length }} Subcategories
                    </span>
                </div>
                <Link
                    :href="categoryShow.url(category.slug)"
                    prefetch
                    class="group/link flex items-center gap-1 text-xs font-semibold text-sage-700 hover:text-sage-900"
                >
                    <span>Explore All {{ category.name }}</span>
                    <span
                        class="transition-transform group-hover/link:translate-x-0.5"
                        >&rarr;</span
                    >
                </Link>
            </div>

            <!-- Multi-Column Grid dynamically balanced -->
            <div
                class="grid max-h-[65vh] gap-x-6 gap-y-2 overflow-y-auto pr-1"
                :class="layout.gridColsClass"
            >
                <div
                    v-for="(column, colIdx) in layout.columns"
                    :key="colIdx"
                    class="space-y-1"
                >
                    <Link
                        v-for="child in column"
                        :key="child.id"
                        :href="categoryShow.url(child.slug)"
                        prefetch
                        class="block rounded-md px-2.5 py-1.5 text-xs text-charcoal-700 transition-colors hover:bg-sage-100/70 hover:text-sage-900"
                    >
                        {{ child.name }}
                    </Link>
                </div>
            </div>

            <!-- Bottom quick bar for mega-menu -->
            <div
                v-if="layout.isMegaMenu"
                class="text-charcoal-500 mt-4 flex items-center justify-between border-t border-cream-200/80 pt-3 text-[11px]"
            >
                <span
                    >Clean botanical ingredients &bull; 100% cruelty-free</span
                >
                <Link
                    :href="categoryShow.url(category.slug)"
                    prefetch
                    class="font-medium text-sage-700 hover:text-sage-900 hover:underline"
                >
                    Shop full collection &rarr;
                </Link>
            </div>

            <!-- Simple view all link for smaller categories -->
            <div v-else class="mt-2.5 border-t border-cream-200/80 pt-2">
                <Link
                    :href="categoryShow.url(category.slug)"
                    prefetch
                    class="flex items-center justify-between rounded-md px-2 py-1 text-xs font-semibold text-sage-700 hover:bg-sage-50 hover:text-sage-900"
                >
                    <span>All {{ category.name }}</span>
                    <span>&rarr;</span>
                </Link>
            </div>
        </div>
    </div>
</template>
