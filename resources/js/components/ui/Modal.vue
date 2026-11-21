<script setup lang="ts">
import { XIcon } from '@lucide/vue';
import { computed, onMounted, ref, useId } from 'vue';
import IconButton from '@/components/ui/IconButton.vue';
import { useDialogBehaviour } from '@/composables/useDialogBehaviour';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        description?: string;
        size?: 'sm' | 'md' | 'lg';
    }>(),
    {
        size: 'md',
    },
);

const emit = defineEmits<{
    close: [];
}>();

const panel = ref<HTMLElement | null>(null);
const titleId = useId();
const descriptionId = useId();
const isOpen = computed(() => props.open);

useDialogBehaviour(isOpen, panel, () => emit('close'));

// Teleported content is client-only: rendering it during SSR breaks hydration.
const isMounted = ref(false);
onMounted(() => {
    isMounted.value = true;
});

const sizeClasses = {
    sm: 'max-w-md',
    md: 'max-w-xl',
    lg: 'max-w-3xl',
} as const;
</script>

<template>
    <Teleport v-if="isMounted" to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-end justify-center bg-charcoal-950/40 p-4 backdrop-blur-sm sm:items-center sm:p-6"
                @click.self="emit('close')"
            >
                <div
                    ref="panel"
                    role="dialog"
                    aria-modal="true"
                    :aria-labelledby="titleId"
                    :aria-describedby="description ? descriptionId : undefined"
                    tabindex="-1"
                    :class="
                        cn(
                            'flex max-h-[90vh] w-full flex-col rounded-2xl bg-surface-raised shadow-xl outline-none',
                            sizeClasses[size],
                        )
                    "
                >
                    <header
                        class="flex shrink-0 items-start justify-between gap-4 p-6 pb-4"
                    >
                        <div class="flex flex-col gap-2">
                            <h2 :id="titleId" class="font-serif text-2xl text-ink">
                                {{ title }}
                            </h2>
                            <p
                                v-if="description"
                                :id="descriptionId"
                                class="text-sm text-ink-muted"
                            >
                                {{ description }}
                            </p>
                        </div>
                        <IconButton
                            label="Close"
                            size="sm"
                            class="-mt-1 -mr-2"
                            @click="emit('close')"
                        >
                            <XIcon class="size-4" aria-hidden="true" />
                        </IconButton>
                    </header>

                    <div class="min-h-0 flex-1 overflow-y-auto px-6 pb-6">
                        <slot />
                    </div>

                    <footer
                        v-if="$slots.footer"
                        class="flex shrink-0 flex-col-reverse gap-3 border-t border-border px-6 py-4 sm:flex-row sm:justify-end"
                    >
                        <slot name="footer" />
                    </footer>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
