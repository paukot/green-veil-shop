<script setup lang="ts">
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { SharedProps } from '@/types/storefront';
import { CheckIcon, TriangleAlertIcon, XIcon } from '@lucide/vue';
import { cn } from '@/lib/utils';

const page = usePage<SharedProps>();

const showToast = ref(false);
const toastMessage = ref('');
const toastType = ref<'success' | 'error'>('success');
let timer: ReturnType<typeof setTimeout> | null = null;

watch(
    () => page.props.flash,
    (newFlash) => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }

        if (newFlash?.success) {
            toastMessage.value = newFlash.success;
            toastType.value = 'success';
            showToast.value = true;
            timer = setTimeout(() => {
                showToast.value = false;
            }, 4000);
        } else if (newFlash?.error) {
            toastMessage.value = newFlash.error;
            toastType.value = 'error';
            showToast.value = true;
            timer = setTimeout(() => {
                showToast.value = false;
            }, 5000);
        }
    },
    { immediate: true, deep: true },
);
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-2 opacity-0"
        leave-active-class="transition duration-200 ease-in"
        leave-to-class="opacity-0"
    >
        <div
            v-if="showToast"
            :class="
                cn(
                    'pointer-events-auto fixed inset-x-4 bottom-4 z-50 flex items-center gap-3 rounded-2xl p-4 shadow-xl sm:inset-x-auto sm:right-6 sm:bottom-6 sm:w-96',
                    toastType === 'success'
                        ? 'bg-rose-950 text-cream-50'
                        : 'bg-danger-700 text-white',
                )
            "
            :role="toastType === 'success' ? 'status' : 'alert'"
        >
            <span
                :class="
                    cn(
                        'flex size-8 shrink-0 items-center justify-center rounded-full',
                        toastType === 'success'
                            ? 'bg-rose-800 text-rose-200'
                            : 'bg-danger-800 text-danger-100',
                    )
                "
            >
                <CheckIcon
                    v-if="toastType === 'success'"
                    class="size-4"
                    aria-hidden="true"
                />
                <TriangleAlertIcon v-else class="size-4" aria-hidden="true" />
            </span>
            <p class="flex-1 text-sm">
                {{ toastMessage }}
            </p>
            <button
                type="button"
                @click="showToast = false"
                class="flex size-8 shrink-0 items-center justify-center rounded-full text-cream-200 transition-colors hover:bg-white/10 hover:text-white"
                aria-label="Dismiss notification"
            >
                <XIcon class="size-4" aria-hidden="true" />
            </button>
        </div>
    </Transition>
</template>
