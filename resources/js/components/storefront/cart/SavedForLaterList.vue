<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { show as productShow } from '@/actions/App/Http/Controllers/ProductController';
import type { LocalCartItem } from '@/composables/useCart';
import { formatCurrency, resolveStorageUrl } from '@/lib/utils';

defineProps<{
    items: LocalCartItem[];
}>();

const emit = defineEmits<{
    (e: 'move-to-cart', productId: number | string): void;
    (e: 'remove-item', productId: number | string): void;
}>();
</script>

<template>
    <div
        v-if="items.length > 0"
        class="overflow-hidden rounded-2xl border border-cream-200 bg-white p-6 shadow-xs"
    >
        <div
            class="mb-4 flex items-center justify-between border-b border-cream-100 pb-3"
        >
            <div class="flex items-center gap-2">
                <svg
                    class="h-5 w-5 text-sage-700"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z"
                    />
                </svg>
                <h3 class="font-serif text-base text-sage-900">
                    Saved For Later ({{ items.length }})
                </h3>
            </div>
            <span class="text-charcoal-500 text-xs">
                Items kept in your ritual sanctuary
            </span>
        </div>

        <div class="divide-y divide-cream-100">
            <div
                v-for="item in items"
                :key="item.product_id"
                class="flex flex-col items-start justify-between gap-4 py-4 first:pt-1 last:pb-1 sm:flex-row sm:items-center"
            >
                <!-- Thumbnail & Information -->
                <div class="flex min-w-0 items-center gap-4">
                    <Link
                        v-if="item.slug"
                        :href="productShow.url(item.slug)"
                        class="h-16 w-16 shrink-0 overflow-hidden rounded-xl border border-cream-200 bg-cream-50"
                    >
                        <img
                            v-if="item.main_image"
                            :src="resolveStorageUrl(item.main_image)"
                            :alt="item.name"
                            class="h-full w-full object-cover"
                            loading="lazy"
                        />
                        <div
                            v-else
                            class="text-sage-400 flex h-full w-full items-center justify-center text-sm"
                        >
                            🍃
                        </div>
                    </Link>
                    <div
                        v-else
                        class="h-16 w-16 shrink-0 overflow-hidden rounded-xl border border-cream-200 bg-cream-50"
                    >
                        <img
                            v-if="item.main_image"
                            :src="resolveStorageUrl(item.main_image)"
                            :alt="item.name"
                            class="h-full w-full object-cover"
                            loading="lazy"
                        />
                        <div
                            v-else
                            class="text-sage-400 flex h-full w-full items-center justify-center text-sm"
                        >
                            🍃
                        </div>
                    </div>

                    <div class="min-w-0">
                        <Link
                            v-if="item.slug"
                            :href="productShow.url(item.slug)"
                            class="truncate font-serif text-sm font-medium text-sage-900 hover:text-sage-700"
                        >
                            {{ item.name }}
                        </Link>
                        <span
                            v-else
                            class="truncate font-serif text-sm font-medium text-sage-900"
                        >
                            {{ item.name }}
                        </span>
                        <p class="text-charcoal-500 mt-0.5 text-xs">
                            {{ formatCurrency(item.price) }}
                        </p>
                    </div>
                </div>

                <!-- Actions: Move back to bag & remove -->
                <div
                    class="flex w-full items-center justify-between gap-2 sm:w-auto sm:justify-end"
                >
                    <button
                        type="button"
                        @click="emit('move-to-cart', item.product_id)"
                        class="inline-flex min-h-[38px] items-center gap-1.5 rounded-full border border-sage-700 bg-sage-50 px-4 py-2 text-xs font-semibold tracking-wider text-sage-900 uppercase transition-colors hover:bg-sage-100"
                    >
                        <svg
                            class="h-3.5 w-3.5 text-sage-700"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        <span>Move to Bag</span>
                    </button>

                    <button
                        type="button"
                        @click="emit('remove-item', item.product_id)"
                        class="text-charcoal-400 flex min-h-[38px] min-w-[38px] items-center justify-center rounded-full p-2 transition-colors hover:bg-rose-50 hover:text-rose-600"
                        aria-label="Remove from saved items"
                        title="Remove"
                    >
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
