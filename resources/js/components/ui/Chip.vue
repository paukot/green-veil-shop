<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { XIcon } from '@lucide/vue';
import { cn } from '@/lib/utils';

defineOptions({
    inheritAttrs: false,
});

const props = withDefaults(
    defineProps<{
        /** Link target; without it the chip is a button (or static with `static`). */
        href?: string;
        selected?: boolean;
        /** Show a remove control and emit `remove` from it. */
        removable?: boolean;
        removeLabel?: string;
        static?: boolean;
        class?: string;
    }>(),
    {
        selected: false,
        removable: false,
        removeLabel: 'Remove',
        static: false,
    },
);

const emit = defineEmits<{
    remove: [];
}>();
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex h-8 shrink-0 items-center rounded-full border text-xs font-medium whitespace-nowrap transition-colors',
                selected
                    ? 'border-rose-900 bg-rose-900 text-cream-50'
                    : 'border-border-strong bg-surface-raised text-ink',
                !props.static && !selected && 'hover:border-ink',
                props.class,
            )
        "
    >
        <component
            :is="href ? Link : props.static ? 'span' : 'button'"
            :href="href"
            :type="href || props.static ? undefined : 'button'"
            :aria-pressed="href || props.static ? undefined : selected"
            :aria-current="href && selected ? 'true' : undefined"
            v-bind="$attrs"
            :class="
                cn(
                    'inline-flex h-full items-center gap-2 px-4',
                    removable && 'pr-2',
                )
            "
        >
            <slot />
        </component>
        <button
            v-if="removable"
            type="button"
            :aria-label="removeLabel"
            class="mr-1 inline-flex size-6 items-center justify-center rounded-full transition-colors hover:bg-ink/10"
            @click="emit('remove')"
        >
            <XIcon class="size-3" aria-hidden="true" />
        </button>
    </span>
</template>
