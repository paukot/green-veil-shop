<script setup lang="ts">
import Skeleton from '@/components/ui/Skeleton.vue';

withDefaults(
    defineProps<{
        count?: number;
    }>(),
    {
        count: 4,
    },
);
</script>

<template>
    <div
        class="divide-y divide-cream-100 p-2"
        role="status"
        aria-label="Searching formulas"
    >
        <!-- Category Pill Skeletons -->
        <div
            class="mb-2 flex items-center gap-2 rounded-xl bg-cream-50/50 p-2.5"
        >
            <Skeleton class="h-3 w-28" rounded="sm" />
            <Skeleton class="h-6 w-20" rounded="lg" />
            <Skeleton class="h-6 w-24" rounded="lg" />
        </div>

        <!-- Formula Row Skeletons -->
        <div
            v-for="i in count"
            :key="i"
            class="flex items-center gap-3 p-3"
            aria-hidden="true"
        >
            <Skeleton class="h-12 w-12 shrink-0" rounded="xl" />
            <div class="min-w-0 flex-1 space-y-1.5">
                <div class="flex items-center gap-2">
                    <Skeleton class="h-3.5 w-3/5" rounded="sm" />
                    <Skeleton class="h-3 w-16" rounded="sm" />
                </div>
                <div class="flex items-center gap-2 pt-0.5">
                    <Skeleton class="h-3 w-12" rounded="sm" />
                    <Skeleton class="h-3 w-10" rounded="sm" />
                </div>
            </div>
        </div>
        <span class="sr-only">Searching botanical formulas...</span>
    </div>
</template>
