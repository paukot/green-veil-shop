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
    <div class="flex flex-col gap-4" role="status" aria-label="Loading orders">
        <div
            v-for="i in count"
            :key="i"
            class="flex items-center gap-6 rounded-2xl border border-border p-6"
            aria-hidden="true"
        >
            <Skeleton class="size-16 shrink-0" />
            <div class="flex flex-1 flex-col gap-2">
                <Skeleton class="h-4 w-32" />
                <Skeleton class="h-4 w-48" />
            </div>
            <Skeleton rounded="full" class="hidden h-8 w-24 sm:block" />
        </div>
    </div>
</template>
