<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { SharedProps } from '@/types/storefront';
import { useFavourites } from '@/composables/useFavourites';
import { HeartIcon } from '@lucide/vue';

const props = withDefaults(
    defineProps<{
        productId: number;
        productName?: string;
        size?: 'sm' | 'md' | 'lg';
        variant?: 'circle' | 'pill' | 'icon';
    }>(),
    {
        size: 'md',
        variant: 'circle',
    },
);

const page = usePage<SharedProps>();
const favourites = useFavourites();

const isFavourited = computed(() => {
    if (favourites.productIds.value.length > 0) {
        return favourites.isFavourited(props.productId);
    }
    return (
        favourites.isFavourited(props.productId) ||
        (page.props.favourites?.includes(props.productId) ?? false)
    );
});

function toggle() {
    favourites.toggle(props.productId);
}

/** Every variant is a 3rem target (2rem for `sm`); the icon is 1rem, or 1.5rem for `lg`. */
const iconSizeClasses = computed(() =>
    props.size === 'lg' ? 'size-6' : 'size-4',
);

const wrapperClasses = computed(() => {
    if (props.variant === 'pill') {
        return 'h-12 gap-2 rounded-full border border-border-strong bg-surface-raised px-6 text-xs font-semibold tracking-wider uppercase hover:border-ink';
    }

    const size = props.size === 'sm' ? 'size-8' : 'size-12';

    if (props.variant === 'icon') {
        return `${size} rounded-full hover:bg-surface-sunken`;
    }

    return `${size} rounded-full bg-surface-raised/90 shadow-sm backdrop-blur hover:bg-surface-raised`;
});
</script>

<template>
    <button
        type="button"
        @click.stop="toggle"
        :class="[
            wrapperClasses,
            'inline-flex shrink-0 items-center justify-center transition-colors',
            isFavourited ? 'text-clay-600' : 'text-ink hover:text-clay-600',
        ]"
        :aria-pressed="isFavourited"
        :aria-label="
            variant === 'pill'
                ? undefined
                : isFavourited
                  ? `Remove ${productName || 'product'} from favourites`
                  : `Save ${productName || 'product'} to favourites`
        "
    >
        <HeartIcon
            :class="[
                iconSizeClasses,
                'transition-transform active:scale-125',
                isFavourited && 'fill-current',
            ]"
            aria-hidden="true"
        />
        <span v-if="variant === 'pill'" class="select-none">
            {{ isFavourited ? 'Saved' : 'Save to wishlist' }}
        </span>
    </button>
</template>
