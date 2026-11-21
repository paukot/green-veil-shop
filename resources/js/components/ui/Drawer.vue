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
        side?: 'left' | 'right';
        /** Panel width on larger screens; phones always keep a 3rem strip of backdrop. */
        size?: 'sm' | 'md';
        tone?: 'raised' | 'surface';
    }>(),
    {
        side: 'right',
        size: 'md',
        tone: 'raised',
    },
);

const emit = defineEmits<{
    close: [];
}>();

const panel = ref<HTMLElement | null>(null);
const titleId = useId();
const isOpen = computed(() => props.open);

useDialogBehaviour(isOpen, panel, () => emit('close'));

// Teleported content is client-only: rendering it during SSR breaks hydration.
const isMounted = ref(false);
onMounted(() => {
    isMounted.value = true;
});
</script>

<template>
    <Teleport v-if="isMounted" to="body">
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-200"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-50 bg-charcoal-950/40 backdrop-blur-sm"
                aria-hidden="true"
                @click="emit('close')"
            />
        </Transition>

        <Transition
            enter-active-class="transition-transform duration-300 ease-out"
            :enter-from-class="
                side === 'right' ? 'translate-x-full' : '-translate-x-full'
            "
            leave-active-class="transition-transform duration-200 ease-in"
            :leave-to-class="
                side === 'right' ? 'translate-x-full' : '-translate-x-full'
            "
        >
            <div
                v-if="open"
                ref="panel"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="titleId"
                tabindex="-1"
                :class="
                    cn(
                        'pointer-events-none fixed inset-y-0 z-50 flex w-full flex-col outline-none',
                        side === 'right' ? 'right-0 pl-12 sm:pl-0' : 'left-0 pr-12 sm:pr-0',
                        size === 'sm' ? 'sm:max-w-sm' : 'sm:max-w-md',
                    )
                "
            >
                <div
                    :class="
                        cn(
                            'pointer-events-auto flex h-full flex-col shadow-xl',
                            tone === 'raised'
                                ? 'bg-surface-raised'
                                : 'bg-surface',
                        )
                    "
                >
                    <header
                        class="flex h-16 shrink-0 items-center justify-between gap-4 border-b border-border px-6"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <h2
                                :id="titleId"
                                class="truncate font-serif text-xl text-ink"
                            >
                                {{ title }}
                            </h2>
                            <slot name="title-extra" />
                        </div>
                        <IconButton
                            label="Close"
                            size="sm"
                            class="-mr-2"
                            @click="emit('close')"
                        >
                            <XIcon class="size-4" aria-hidden="true" />
                        </IconButton>
                    </header>

                    <div class="min-h-0 flex-1 overflow-y-auto">
                        <slot />
                    </div>

                    <footer
                        v-if="$slots.footer"
                        class="shrink-0 border-t border-border p-6 pb-[max(1.5rem,env(safe-area-inset-bottom))]"
                    >
                        <slot name="footer" />
                    </footer>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
