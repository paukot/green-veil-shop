<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { show as productShow } from '@/actions/App/Http/Controllers/ProductController';
import { QuantityInput } from '@/components/form';
import type { LocalCartItem } from '@/composables/useCart';
import {
    formatCurrency,
    resolveStorageUrl,
    showPlaceholderOnError,
} from '@/lib/utils';
import {
    BookmarkIcon,
    Trash2Icon,
    TriangleAlertIcon,
    XIcon,
} from '@lucide/vue';
import FreeShippingProgress from '@/components/storefront/cart/FreeShippingProgress.vue';
import Badge from '@/components/ui/Badge.vue';
import Card from '@/components/ui/Card.vue';
import IconButton from '@/components/ui/IconButton.vue';

defineProps<{
    items: LocalCartItem[];
    warnings?: string[];
    freeShippingProgress: number;
    qualifiesForFreeShipping: boolean;
    amountUntilFreeShipping: number;
}>();

const emit = defineEmits<{
    (e: 'dismiss-warnings'): void;
    (e: 'update-quantity', productId: number, quantity: number): void;
    (e: 'remove-item', productId: number): void;
    (e: 'save-for-later', productId: number): void;
}>();
</script>

<template>
    <div class="flex flex-col gap-6">
        <div
            v-if="warnings && warnings.length > 0"
            class="flex items-start gap-3 rounded-2xl border border-warning-200 bg-warning-50 p-4"
            role="alert"
        >
            <TriangleAlertIcon
                class="mt-1 size-4 shrink-0 text-warning-600"
                aria-hidden="true"
            />
            <div class="flex flex-1 flex-col gap-1 text-sm text-warning-800">
                <p class="font-medium">Your bag was updated</p>
                <ul
                    class="flex flex-col gap-1 text-warning-700"
                    aria-label="Bag warnings"
                >
                    <li v-for="(warn, idx) in warnings" :key="idx">
                        {{ warn }}
                    </li>
                </ul>
            </div>
            <IconButton
                label="Dismiss bag warnings"
                size="sm"
                class="-mt-1 -mr-1 text-warning-700 hover:bg-warning-100"
                @click="emit('dismiss-warnings')"
            >
                <XIcon class="size-4" aria-hidden="true" />
            </IconButton>
        </div>

        <Card>
            <FreeShippingProgress
                :progress="freeShippingProgress"
                :qualifies="qualifiesForFreeShipping"
                :amount-remaining="amountUntilFreeShipping"
            />
        </Card>

        <ul
            class="flex flex-col divide-y divide-border border-y border-border"
            aria-label="Bag items"
        >
            <li
                v-for="item in items"
                :key="item.product_id"
                class="flex gap-4 py-6 sm:gap-6"
            >
                <Link
                    :href="productShow.url(item.slug)"
                    prefetch
                    aria-hidden="true"
                    tabindex="-1"
                    class="size-24 shrink-0 overflow-hidden rounded-lg bg-surface-sunken sm:size-32"
                >
                    <img
                        v-if="item.main_image"
                        :src="resolveStorageUrl(item.main_image)"
                        alt=""
                        class="size-full object-cover"
                        loading="lazy"
                        @error="showPlaceholderOnError"
                    />
                </Link>

                <div class="flex min-w-0 flex-1 flex-col gap-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 flex-col gap-1">
                            <p
                                v-if="item.category"
                                class="text-xs font-semibold tracking-wider text-ink-subtle uppercase"
                            >
                                {{ item.category.name }}
                            </p>
                            <Link
                                :href="productShow.url(item.slug)"
                                prefetch
                                class="font-serif text-lg leading-6 text-ink hover:text-rose-800"
                            >
                                {{ item.name }}
                            </Link>
                            <p class="text-sm text-ink-muted tabular-nums">
                                {{ formatCurrency(item.price) }} each
                            </p>
                            <Badge
                                v-if="
                                    item.track_inventory &&
                                    item.stock_quantity <= 5
                                "
                                tone="warning"
                                class="self-start"
                            >
                                Only {{ item.stock_quantity }} left
                            </Badge>
                        </div>
                        <p
                            class="text-base font-semibold text-ink tabular-nums"
                        >
                            {{ formatCurrency(item.price * item.quantity) }}
                        </p>
                    </div>

                    <div
                        class="mt-auto flex items-center justify-between gap-2"
                    >
                        <QuantityInput
                            size="sm"
                            :aria-label="`Quantity for ${item.name}`"
                            :model-value="item.quantity"
                            :min="1"
                            :max="
                                item.track_inventory ? item.stock_quantity : 99
                            "
                            @change="
                                (qty) =>
                                    emit(
                                        'update-quantity',
                                        item.product_id,
                                        qty,
                                    )
                            "
                        />
                        <div class="flex items-center gap-2">
                            <IconButton
                                :label="`Save ${item.name} for later`"
                                size="sm"
                                @click="emit('save-for-later', item.product_id)"
                            >
                                <BookmarkIcon
                                    class="size-4"
                                    aria-hidden="true"
                                />
                            </IconButton>
                            <IconButton
                                :label="`Remove ${item.name} from bag`"
                                size="sm"
                                class="hover:text-danger-600"
                                @click="emit('remove-item', item.product_id)"
                            >
                                <Trash2Icon class="size-4" aria-hidden="true" />
                            </IconButton>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</template>
