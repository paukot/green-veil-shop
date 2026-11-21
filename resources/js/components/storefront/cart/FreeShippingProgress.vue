<script setup lang="ts">
import { CheckIcon, TruckIcon } from '@lucide/vue';
import { formatCurrency } from '@/lib/utils';

defineProps<{
    /** 0–100 */
    progress: number;
    qualifies: boolean;
    amountRemaining: number;
}>();
</script>

<template>
    <div class="flex flex-col gap-3">
        <p class="flex items-center gap-2 text-sm text-ink">
            <CheckIcon
                v-if="qualifies"
                class="size-4 text-success-700"
                aria-hidden="true"
            />
            <TruckIcon
                v-else
                class="size-4 text-ink-subtle"
                aria-hidden="true"
            />
            <span v-if="qualifies" class="font-medium text-success-700">
                Free delivery unlocked
            </span>
            <span v-else>
                Add
                <span class="font-semibold">{{
                    formatCurrency(amountRemaining)
                }}</span>
                for free delivery
            </span>
        </p>
        <div
            class="h-1 w-full overflow-hidden rounded-full bg-surface-sunken"
            role="progressbar"
            :aria-valuenow="progress"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-label="Progress to free delivery"
        >
            <div
                class="h-full rounded-full bg-sage-700 transition-[width] duration-500 ease-out"
                :style="{ width: `${progress}%` }"
            />
        </div>
    </div>
</template>
