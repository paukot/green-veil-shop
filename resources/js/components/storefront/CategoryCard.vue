<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import type { CategoryCardData } from '@/types/storefront';
import { show as categoryShow } from '@/actions/App/Http/Controllers/CategoryController';
import { resolveStorageUrl } from '@/lib/utils';

const props = defineProps<{
    category: CategoryCardData;
}>();

const imageUrl = computed(() => resolveStorageUrl(props.category.image));
</script>

<template>
    <Link
        :href="categoryShow.url(category.slug)"
        prefetch
        class="group relative flex min-h-[240px] flex-col justify-end overflow-hidden rounded-2xl border border-cream-200/60 bg-sage-950 p-6 shadow-xs transition-all duration-300 hover:shadow-lg sm:min-h-[260px] sm:p-8"
    >
        <!-- Background image or botanical texture -->
        <div v-if="imageUrl" class="absolute inset-0 z-0">
            <img
                :src="imageUrl"
                :alt="category.name"
                class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                loading="lazy"
            />
            <!-- Darkening overlay like slider to make text crisp and legible -->
            <div
                class="absolute inset-0 bg-black/25 bg-linear-to-t from-black/80 via-black/40 to-transparent transition-opacity duration-300 group-hover:from-black/85"
            ></div>
        </div>
        <div
            v-else
            class="absolute inset-0 z-0 bg-linear-to-br from-sage-900 via-sage-800 to-charcoal-900"
        >
            <div
                class="absolute -top-8 -right-8 h-40 w-40 rounded-full bg-sage-700/20 blur-2xl"
            ></div>
        </div>

        <!-- Content -->
        <div class="relative z-10 space-y-1.5">
            <h3
                class="font-serif text-xl font-normal text-cream-50 transition-colors duration-200 group-hover:text-white sm:text-2xl"
            >
                {{ category.name }}
            </h3>
            <p
                v-if="category.description"
                class="line-clamp-2 text-xs leading-relaxed font-light text-cream-100/90"
            >
                {{ category.description }}
            </p>
            <span
                class="inline-flex items-center gap-1.5 pt-1.5 text-xs font-semibold tracking-wider text-cream-200 uppercase transition-all duration-200 group-hover:translate-x-1 group-hover:text-white"
            >
                <span>Explore</span>
                <svg
                    class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5"
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
