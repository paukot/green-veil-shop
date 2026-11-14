<script setup lang="ts">
import Skeleton from '@/components/ui/Skeleton.vue';

withDefaults(
    defineProps<{
        count?: number;
    }>(),
    {
        count: 3,
    },
);
</script>

<template>
    <div class="space-y-4" role="status" aria-label="Loading orders">
        <article
            v-for="i in count"
            :key="i"
            class="overflow-hidden rounded-2xl border border-cream-200 bg-white shadow-xs"
            aria-hidden="true"
        >
            <!-- Header bar -->
            <div
                class="border-b border-cream-100 bg-cream-50/60 p-4 sm:flex sm:items-center sm:justify-between sm:px-6"
            >
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                    <Skeleton class="h-4 w-28" rounded="sm" />
                    <Skeleton class="h-3 w-20" rounded="sm" />
                    <Skeleton class="h-4 w-16" rounded="sm" />
                </div>
                <div class="mt-2 flex items-center gap-2 sm:mt-0">
                    <Skeleton class="h-5 w-20" rounded="full" />
                    <Skeleton class="h-5 w-16" rounded="full" />
                </div>
            </div>

            <!-- Items preview -->
            <div class="p-4 sm:flex sm:items-center sm:justify-between sm:p-6">
                <div class="flex items-center gap-3">
                    <Skeleton
                        class="h-14 w-14 shrink-0 bg-cream-100"
                        rounded="xl"
                    />
                    <Skeleton
                        class="h-14 w-14 shrink-0 bg-cream-100"
                        rounded="xl"
                    />
                    <div class="ml-2 space-y-1.5">
                        <Skeleton class="h-4 w-40" rounded="sm" />
                        <Skeleton class="h-3 w-24" rounded="sm" />
                    </div>
                </div>
                <div class="mt-4 sm:mt-0">
                    <Skeleton class="h-8 w-24" rounded="xl" />
                </div>
            </div>
        </article>
        <span class="sr-only">Loading your previous orders...</span>
    </div>
</template>
