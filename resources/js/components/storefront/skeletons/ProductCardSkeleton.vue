<script setup lang="ts">
import Skeleton from '@/components/ui/Skeleton.vue';
</script>

<template>
    <div
        class="relative flex flex-col overflow-hidden rounded-2xl border border-cream-200 bg-white"
        aria-hidden="true"
    >
        <!-- Image Area Placeholder -->
        <div class="relative aspect-square w-full overflow-hidden bg-cream-100">
            <Skeleton class="h-full w-full" rounded="none" />
            <!-- Simulated Badge Chip -->
            <div class="pointer-events-none absolute top-3 left-3 z-10">
                <Skeleton class="h-4 w-14 rounded-full bg-cream-300/80" />
            </div>
            <!-- Simulated Wishlist Circle Button -->
            <div class="pointer-events-none absolute top-3 right-3 z-10">
                <Skeleton class="h-8 w-8 rounded-full bg-white/80 shadow-2xs" />
            </div>
        </div>

        <!-- Product Details Placeholder -->
        <div class="flex flex-1 flex-col justify-between p-3.5 sm:p-4.5">
            <div class="space-y-2">
                <!-- Brand / Category line -->
                <Skeleton class="h-3 w-16" rounded="sm" />
                <!-- Title lines (2 lines to prevent height shift) -->
                <Skeleton class="h-4 w-5/6" rounded="sm" />
                <Skeleton class="h-4 w-1/2" rounded="sm" />
                <!-- Price and volume row -->
                <div class="flex items-center gap-2 pt-1">
                    <Skeleton class="h-4.5 w-16" rounded="sm" />
                    <Skeleton class="h-3 w-10" rounded="sm" />
                </div>
            </div>

            <!-- Action Button Placeholder -->
            <div class="mt-4 pt-2">
                <Skeleton class="h-10 w-full" rounded="xl" />
            </div>
        </div>
    </div>
</template>
