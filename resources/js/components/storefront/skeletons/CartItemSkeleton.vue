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
    <ul
        class="flex flex-col divide-y divide-border"
        role="status"
        aria-label="Loading bag"
    >
        <li
            v-for="i in count"
            :key="i"
            class="flex gap-4 py-4"
            aria-hidden="true"
        >
            <Skeleton class="size-16 shrink-0" />
            <div class="flex flex-1 flex-col gap-2">
                <div class="flex justify-between gap-4">
                    <Skeleton class="h-4 w-3/5" />
                    <Skeleton class="h-4 w-12" />
                </div>
                <Skeleton rounded="full" class="h-8 w-24" />
            </div>
        </li>
    </ul>
</template>
