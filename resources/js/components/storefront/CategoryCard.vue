<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import type { CategoryCardData } from '@/types/storefront';

const props = defineProps<{
    category: CategoryCardData;
}>();

const imageUrl = computed(() => {
    if (!props.category.image) return null;
    if (
        props.category.image.startsWith('http') ||
        props.category.image.startsWith('/')
    ) {
        return props.category.image;
    }
    return `/storage/${props.category.image}`;
});
</script>

<template>
    <Link
        :href="`/categories/${category.slug}`"
        class="group relative flex min-h-[220px] flex-col justify-end overflow-hidden rounded-2xl border border-cream-200 bg-cream-100 p-6 transition-all duration-300 hover:shadow-md sm:p-8"
    >
        <!-- Background image or botanical texture -->
        <div v-if="imageUrl" class="absolute inset-0 z-0">
            <img
                :src="imageUrl"
                :alt="category.name"
                class="h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-105"
                loading="lazy"
            />
            <div
                class="absolute inset-0 bg-gradient-to-t from-cream-100 via-cream-100/60 to-transparent"
            ></div>
        </div>
        <div
            v-else
            class="absolute inset-0 z-0 bg-gradient-to-br from-sage-50 to-cream-100"
        ></div>

        <!-- Content -->
        <div class="relative z-10 space-y-1">
            <h3
                class="font-serif text-xl text-charcoal-900 transition-colors group-hover:text-sage-800 sm:text-2xl"
            >
                {{ category.name }}
            </h3>
            <p
                v-if="category.description"
                class="text-charcoal-600 line-clamp-2 text-xs"
            >
                {{ category.description }}
            </p>
            <span
                class="inline-flex items-center gap-1.5 pt-2 text-xs font-semibold text-sage-700 transition-transform group-hover:translate-x-1"
            >
                <span>Explore</span>
                <svg
                    class="h-3.5 w-3.5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M17 8l4 4m0 0l-4 4m4-4H3"
                    />
                </svg>
            </span>
        </div>
    </Link>
</template>
