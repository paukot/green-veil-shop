<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowRightIcon } from '@lucide/vue';
import type { CategoryCardData } from '@/types/storefront';
import { show as categoryShow } from '@/actions/App/Http/Controllers/CategoryController';
import { resolveStorageUrl, showPlaceholderOnError } from '@/lib/utils';

const props = defineProps<{
    category: CategoryCardData;
}>();

const imageUrl = computed(() => resolveStorageUrl(props.category.image));
</script>

<template>
    <Link
        :href="categoryShow.url(category.slug)"
        prefetch
        class="group relative flex aspect-[4/5] flex-col justify-end overflow-hidden rounded-2xl bg-rose-900"
    >
        <img
            v-if="imageUrl"
            :src="imageUrl"
            :srcset="category.image_srcset ?? undefined"
            sizes="(min-width: 1024px) 33vw, 50vw"
            :alt="category.name"
            class="absolute inset-0 size-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
            loading="lazy"
            @error="showPlaceholderOnError"
        />
        <div
            class="absolute inset-0 bg-linear-to-t from-charcoal-950/80 via-charcoal-950/20 to-transparent"
        />
        <div class="relative flex flex-col gap-2 p-4 text-cream-50 sm:p-6">
            <h3 class="font-serif text-2xl leading-8 sm:text-3xl sm:leading-10">
                {{ category.name }}
            </h3>
            <p
                v-if="category.description"
                class="hidden text-sm text-cream-200 sm:line-clamp-2"
            >
                {{ category.description }}
            </p>
            <span
                class="inline-flex items-center gap-2 text-xs font-semibold tracking-widest uppercase"
            >
                Explore
                <ArrowRightIcon
                    class="size-4 transition-transform group-hover:translate-x-1"
                    aria-hidden="true"
                />
            </span>
        </div>
    </Link>
</template>
