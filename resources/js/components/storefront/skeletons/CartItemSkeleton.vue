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
    <div class="space-y-3" role="status" aria-label="Loading shopping bag">
        <div
            v-for="i in count"
            :key="i"
            class="flex items-start gap-4 rounded-2xl border border-cream-200 bg-white p-3.5"
            aria-hidden="true"
        >
            <Skeleton class="h-16 w-16 shrink-0 bg-cream-100" rounded="xl" />
            <div class="flex-1 space-y-2">
                <Skeleton class="h-3.5 w-4/5" rounded="sm" />
                <Skeleton class="h-3 w-1/3" rounded="sm" />
                <div class="flex items-center justify-between pt-1">
                    <Skeleton class="h-7 w-20" rounded="full" />
                    <Skeleton class="h-4 w-14" rounded="sm" />
                </div>
            </div>
        </div>
        <span class="sr-only">Updating shopping bag items...</span>
    </div>
</template>
