<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { show as productShow } from '@/actions/App/Http/Controllers/ProductController';
import type { LocalCartItem } from '@/composables/useCart';
import {
    formatCurrency,
    resolveStorageUrl,
    showPlaceholderOnError,
} from '@/lib/utils';
import { ShoppingBagIcon, Trash2Icon } from '@lucide/vue';
import Button from '@/components/ui/Button.vue';
import IconButton from '@/components/ui/IconButton.vue';

defineProps<{
    items: LocalCartItem[];
}>();

const emit = defineEmits<{
    (e: 'move-to-cart', productId: number | string): void;
    (e: 'remove-item', productId: number | string): void;
}>();
</script>

<template>
    <section v-if="items.length > 0" class="flex flex-col gap-4">
        <h2 class="flex items-baseline gap-2 font-serif text-2xl text-ink">
            Saved for later
            <span class="font-sans text-sm text-ink-subtle">{{
                items.length
            }}</span>
        </h2>

        <ul class="flex flex-col divide-y divide-border border-y border-border">
            <li
                v-for="item in items"
                :key="item.product_id"
                class="flex items-center gap-4 py-4"
            >
                <component
                    :is="item.slug ? Link : 'div'"
                    :href="item.slug ? productShow.url(item.slug) : undefined"
                    class="size-16 shrink-0 overflow-hidden rounded-lg bg-surface-sunken"
                >
                    <img
                        v-if="item.main_image"
                        :src="resolveStorageUrl(item.main_image)"
                        :alt="item.name"
                        class="size-full object-cover"
                        loading="lazy"
                        @error="showPlaceholderOnError"
                    />
                </component>

                <div class="flex min-w-0 flex-1 flex-col gap-1">
                    <component
                        :is="item.slug ? Link : 'span'"
                        :href="
                            item.slug ? productShow.url(item.slug) : undefined
                        "
                        class="truncate font-serif text-base text-ink hover:text-sage-800"
                    >
                        {{ item.name }}
                    </component>
                    <p class="text-sm text-ink-muted tabular-nums">
                        {{ formatCurrency(item.price) }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        size="sm"
                        variant="outline"
                        @click="emit('move-to-cart', item.product_id)"
                    >
                        <template #icon>
                            <ShoppingBagIcon
                                class="size-4"
                                aria-hidden="true"
                            />
                        </template>
                        <span class="hidden sm:inline">Move to bag</span>
                        <span class="sm:hidden">Add</span>
                    </Button>
                    <IconButton
                        label="Remove from saved items"
                        size="sm"
                        class="hover:text-danger-600"
                        @click="emit('remove-item', item.product_id)"
                    >
                        <Trash2Icon class="size-4" aria-hidden="true" />
                    </IconButton>
                </div>
            </li>
        </ul>
    </section>
</template>
