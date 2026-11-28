<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowRightIcon } from '@lucide/vue';
import Container from '@/components/ui/Container.vue';
import type { CategoryNavigationItem } from '@/types/storefront';
import { show as categoryShow } from '@/actions/App/Http/Controllers/CategoryController';
import { getNavigationLayout } from '@/lib/navigation';
import { cn, resolveStorageUrl, showPlaceholderOnError } from '@/lib/utils';

const props = defineProps<{
    category: CategoryNavigationItem;
}>();

const layout = computed(() => getNavigationLayout(props.category));
</script>

<template>
    <!--
        Full-width panel under the navigation bar. It opens on hover and on keyboard focus
        (focus-within on the parent), and stays invisible to the tab order while closed.
    -->
    <div
        v-if="category.children && category.children.length > 0"
        role="region"
        :aria-label="`${category.name} menu`"
        class="invisible absolute inset-x-0 top-full z-50 border-t border-border bg-surface-raised opacity-0 shadow-lg transition-[opacity,visibility] duration-150 group-focus-within:visible group-focus-within:opacity-100 group-hover:visible group-hover:opacity-100"
    >
        <Container class="grid grid-cols-12 gap-8 py-8">
            <div class="col-span-8 flex flex-col gap-6 xl:col-span-9">
                <div class="flex items-baseline justify-between gap-4">
                    <p class="font-serif text-2xl text-ink">
                        {{ category.name }}
                    </p>
                    <Link
                        :href="categoryShow.url(category.slug)"
                        prefetch
                        class="inline-flex items-center gap-2 text-sm font-medium text-rose-800 hover:underline"
                    >
                        Shop all {{ category.name }}
                        <ArrowRightIcon class="size-4" aria-hidden="true" />
                    </Link>
                </div>

                <div
                    :class="
                        cn(
                            'grid max-h-[60vh] gap-x-8 overflow-y-auto',
                            layout.gridColsClass,
                        )
                    "
                >
                    <ul
                        v-for="(column, colIdx) in layout.columns"
                        :key="colIdx"
                        class="flex flex-col"
                    >
                        <li v-for="child in column" :key="child.id">
                            <Link
                                :href="categoryShow.url(child.slug)"
                                prefetch
                                class="flex h-8 items-center text-sm text-ink-muted transition-colors hover:text-ink"
                            >
                                {{ child.name }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>

            <Link
                :href="categoryShow.url(category.slug)"
                prefetch
                class="group/tile relative col-span-4 hidden aspect-[4/5] max-h-80 overflow-hidden rounded-2xl bg-surface-sunken lg:block xl:col-span-3"
                tabindex="-1"
                aria-hidden="true"
            >
                <img
                    v-if="category.image"
                    :src="resolveStorageUrl(category.image)"
                    :srcset="category.image_srcset ?? undefined"
                    sizes="20rem"
                    alt=""
                    class="size-full object-cover transition-transform duration-700 group-hover/tile:scale-105"
                    loading="lazy"
                    @error="showPlaceholderOnError"
                />
                <div
                    class="absolute inset-0 bg-linear-to-t from-charcoal-950/70 to-transparent"
                />
                <p
                    class="absolute inset-x-0 bottom-0 flex flex-col gap-1 p-6 text-cream-50"
                >
                    <span
                        class="text-xs font-semibold tracking-widest uppercase"
                    >
                        Discover
                    </span>
                    <span class="font-serif text-2xl">{{ category.name }}</span>
                </p>
            </Link>
        </Container>
    </div>
</template>
