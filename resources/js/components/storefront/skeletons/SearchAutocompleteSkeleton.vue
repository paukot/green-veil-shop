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
    <div class="flex flex-col gap-4 p-4" role="status" aria-label="Searching">
        <div class="flex gap-2" aria-hidden="true">
            <Skeleton rounded="full" class="h-8 w-24" />
            <Skeleton rounded="full" class="h-8 w-32" />
        </div>
        <ul class="flex flex-col" aria-hidden="true">
            <li
                v-for="i in count"
                :key="i"
                class="flex h-16 items-center gap-4"
            >
                <Skeleton class="size-12 shrink-0" />
                <div class="flex flex-1 flex-col gap-2">
                    <Skeleton class="h-4 w-3/5" />
                    <Skeleton class="h-3 w-24" />
                </div>
            </li>
        </ul>
    </div>
</template>
