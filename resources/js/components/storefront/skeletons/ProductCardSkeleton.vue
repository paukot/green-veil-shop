<script setup lang="ts">
import Skeleton from '@/components/ui/Skeleton.vue';
</script>

<template>
    <div class="flex flex-col gap-4" aria-hidden="true">
        <Skeleton rounded="2xl" class="aspect-[4/5] w-full" />
        <div class="flex flex-col gap-2">
            <Skeleton class="h-3 w-24" />
            <Skeleton class="h-6 w-full" />
            <Skeleton class="h-6 w-2/3" />
            <div class="flex items-end justify-between gap-2 pt-2">
                <Skeleton class="h-4 w-16" />
                <Skeleton rounded="full" class="size-12" />
            </div>
        </div>
    </div>
</template>
