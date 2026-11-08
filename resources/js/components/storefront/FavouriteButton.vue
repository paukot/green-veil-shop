<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { SharedProps } from '@/types/storefront';
import { useFavourites } from '@/composables/useFavourites';

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

const iconSizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'w-3.5 h-3.5';
        case 'lg':
            return 'w-5 h-5';
        default:
            return 'w-4 h-4';
    }
});

const wrapperClasses = computed(() => {
    if (props.variant === 'pill') {
        return 'px-4 py-2 rounded-full border border-cream-300 bg-white hover:border-sage-500 hover:bg-cream-100 flex items-center gap-2 text-xs font-medium text-charcoal-700 transition-colors shadow-xs';
    }

    if (props.variant === 'icon') {
        return 'p-1 text-charcoal-500 hover:text-rose-600 transition-colors';
    }

    // Default circle
    const sizeMap = {
        sm: 'p-1.5',
        md: 'p-2',
        lg: 'p-3',
    };

    return `${sizeMap[props.size]} rounded-full bg-cream-50/90 hover:bg-white text-charcoal-700 shadow-xs hover:shadow transition-all focus:outline-none focus:ring-2 focus:ring-sage-500`;
});
</script>

<template>
    <button
        type="button"
        @click.stop="toggle"
        :class="[
            wrapperClasses,
            'flex min-h-[44px] min-w-[44px] items-center justify-center',
            isFavourited
                ? 'text-rose-500 hover:text-rose-600'
                : 'text-charcoal-600 hover:text-rose-500',
        ]"
        :aria-label="
            isFavourited
                ? `Remove ${productName || 'product'} from favourites`
                : `Save ${productName || 'product'} to favourites`
        "
    >
        <!-- Solid Heart when favourited -->
        <svg
            v-if="isFavourited"
            :class="[
                iconSizeClasses,
                'fill-current text-rose-500 transition-transform active:scale-125',
            ]"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <path
                d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z"
            />
        </svg>

        <!-- Outline Heart when not favourited -->
        <svg
            v-else
            :class="[
                iconSizeClasses,
                'fill-none stroke-current text-current transition-transform active:scale-125',
            ]"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="1.75"
            aria-hidden="true"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"
            />
        </svg>
        <span v-if="variant === 'pill'" class="select-none">
            {{ isFavourited ? 'Saved to Wishlist' : 'Add to Wishlist' }}
        </span>
    </button>
</template>
