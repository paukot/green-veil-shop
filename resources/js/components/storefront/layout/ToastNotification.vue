<script setup lang="ts">
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { SharedProps } from '@/types/storefront';

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
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="showToast"
            class="pointer-events-auto fixed inset-x-4 bottom-4 z-50 flex w-auto items-start gap-3 rounded-xl border p-4 shadow-lg sm:inset-x-auto sm:right-6 sm:bottom-6 sm:w-full sm:max-w-sm"
            :class="
                toastType === 'success'
                    ? 'border-sage-700 bg-sage-900 text-cream-50'
                    : 'border-red-700 bg-red-900 text-white'
            "
            role="alert"
        >
            <div class="shrink-0 pt-0.5">
                <svg
                    v-if="toastType === 'success'"
                    class="h-5 w-5 text-sage-300"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 13l4 4L19 7"
                    />
                </svg>
                <svg
                    v-else
                    class="h-5 w-5 text-red-300"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                    />
                </svg>
            </div>
            <div class="flex-1 text-sm leading-5">
                {{ toastMessage }}
            </div>
            <button
                type="button"
                @click="showToast = false"
                class="-mt-1 -mr-1 shrink-0 p-1 text-cream-200 hover:text-white"
                aria-label="Dismiss notification"
            >
                &times;
            </button>
        </div>
    </Transition>
</template>
