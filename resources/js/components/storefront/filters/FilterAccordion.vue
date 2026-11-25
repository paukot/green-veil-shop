<script setup lang="ts">
import { ChevronDownIcon } from '@lucide/vue';
import { useId } from 'vue';
import { cn } from '@/lib/utils';

defineProps<{
    title: string;
    /** Number of active selections, shown beside the title. */
    count?: number;
}>();

const isOpen = defineModel<boolean>('isOpen', { default: false });
const panelId = useId();
</script>

<template>
    <div class="border-b border-border py-4">
        <button
            type="button"
            class="flex h-8 w-full items-center justify-between gap-2 text-left text-sm font-medium text-ink"
            :aria-expanded="isOpen"
            :aria-controls="panelId"
            @click="isOpen = !isOpen"
        >
            <span class="flex items-center gap-2">
                {{ title }}
                <span
                    v-if="count"
                    class="flex size-6 items-center justify-center rounded-full bg-rose-100 text-xs font-semibold text-rose-800"
                >
                    {{ count }}
                </span>
            </span>
            <ChevronDownIcon
                :class="
                    cn(
                        'size-4 text-ink-subtle transition-transform',
                        isOpen && 'rotate-180',
                    )
                "
                aria-hidden="true"
            />
        </button>
        <div v-show="isOpen" :id="panelId" class="mt-3 flex flex-col gap-3">
            <slot />
        </div>
    </div>
</template>
