<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, useId, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Chip from '@/components/ui/Chip.vue';
import { fieldStateClasses, fieldSurfaceClasses } from '@/lib/fieldStyles';
import {
    cn,
    formatCurrency,
    resolveStorageUrl,
    showPlaceholderOnError,
} from '@/lib/utils';
import { index as productsIndex } from '@/routes/products';
import { show as categoryShow } from '@/actions/App/Http/Controllers/CategoryController';
import {
    search as productSearch,
    show as productShow,
} from '@/actions/App/Http/Controllers/ProductController';
import SearchAutocompleteSkeleton from '@/components/storefront/skeletons/SearchAutocompleteSkeleton.vue';
import type { ProductCardData } from '@/types/storefront';
import {
    ArrowRightIcon,
    ClockIcon,
    LoaderCircleIcon,
    SearchIcon,
    XIcon,
} from '@lucide/vue';

const props = withDefaults(
    defineProps<{
        placeholder?: string;
        autofocus?: boolean;
    }>(),
    {
        placeholder: 'Search skincare, makeup, concerns…',
        autofocus: false,
    },
);

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const query = ref('');
const isOpen = ref(false);
const isLoading = ref(false);
const results = ref<{
    products: ProductCardData[];
    categories: Array<{ id: number; slug: string; name: string }>;
    total_matches: number;
}>({
    products: [],
    categories: [],
    total_matches: 0,
});

const recentSearches = ref<string[]>([]);
const searchContainer = ref<HTMLElement | null>(null);
const inputRef = ref<HTMLInputElement | null>(null);
let debounceTimeout: ReturnType<typeof setTimeout> | null = null;
let abortController: AbortController | null = null;

const RECENT_SEARCHES_KEY = 'green_veil_recent_searches';
const listboxId = useId();
const activeIndex = ref(-1);

watch(results, () => {
    activeIndex.value = -1;
});

const activeOptionId = computed(() =>
    activeIndex.value >= 0 ? `${listboxId}-${activeIndex.value}` : undefined,
);

onMounted(() => {
    try {
        const stored = localStorage.getItem(RECENT_SEARCHES_KEY);
        if (stored) {
            recentSearches.value = JSON.parse(stored).slice(0, 5);
        }
    } catch {
        recentSearches.value = [];
    }

    if (props.autofocus && inputRef.value) {
        inputRef.value.focus();
    }

    // pointerdown, not click: the header's "Search" click that mounts this component is
    // still bubbling while onMounted runs, and a click listener would treat it as an
    // outside click and close the dropdown the moment it opens.
    document.addEventListener('pointerdown', handleOutsideClick);
});

onUnmounted(() => {
    document.removeEventListener('pointerdown', handleOutsideClick);
    if (debounceTimeout) clearTimeout(debounceTimeout);
    if (abortController) abortController.abort();
});

function handleOutsideClick(event: PointerEvent) {
    if (
        searchContainer.value &&
        !searchContainer.value.contains(event.target as Node)
    ) {
        isOpen.value = false;
    }
}

function saveSearchTerm(term: string) {
    const clean = term.trim();
    if (!clean) return;
    const filtered = recentSearches.value.filter(
        (t) => t.toLowerCase() !== clean.toLowerCase(),
    );
    recentSearches.value = [clean, ...filtered].slice(0, 5);
    try {
        localStorage.setItem(
            RECENT_SEARCHES_KEY,
            JSON.stringify(recentSearches.value),
        );
    } catch {
        // ignore localStorage quota errors
    }
}

function clearRecentSearches() {
    recentSearches.value = [];
    try {
        localStorage.removeItem(RECENT_SEARCHES_KEY);
    } catch {
        // ignore
    }
}

async function fetchSearchResults(searchTerm: string) {
    if (abortController) {
        abortController.abort();
    }
    abortController = new AbortController();

    isLoading.value = true;
    try {
        const response = await fetch(
            productSearch.url({ query: { q: searchTerm } }),
            {
                signal: abortController.signal,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            },
        );

        if (response.ok) {
            const data = await response.json();
            results.value = data;
        }
    } catch (e: unknown) {
        if (e instanceof DOMException && e.name === 'AbortError') {
            return;
        }
        results.value = { products: [], categories: [], total_matches: 0 };
    } finally {
        isLoading.value = false;
    }
}

watch(query, (val) => {
    if (debounceTimeout) clearTimeout(debounceTimeout);

    // Typing always reopens the suggestions, even if they were dismissed earlier.
    isOpen.value = true;

    const trimmed = val.trim();
    if (trimmed.length < 2) {
        results.value = { products: [], categories: [], total_matches: 0 };
        isLoading.value = false;
        return;
    }

    isLoading.value = true;

    debounceTimeout = setTimeout(() => {
        fetchSearchResults(trimmed);
    }, 220);
});

function onInputFocus() {
    isOpen.value = true;
}

function submitFullSearch(termToSearch?: string) {
    const finalTerm = (termToSearch ?? query.value).trim();
    if (!finalTerm) return;

    saveSearchTerm(finalTerm);
    isOpen.value = false;
    emit('close');

    router.get(
        productsIndex.url(),
        { q: finalTerm },
        { preserveState: false, preserveScroll: false },
    );
}

function selectProduct(slug: string, name: string) {
    saveSearchTerm(name);
    isOpen.value = false;
    emit('close');
    router.visit(productShow.url(slug));
}

function selectCategory(slug: string, name: string) {
    saveSearchTerm(name);
    isOpen.value = false;
    emit('close');
    router.visit(categoryShow.url(slug));
}

function handleKeydown(e: KeyboardEvent) {
    const productCount = results.value.products.length;

    if (e.key === 'Escape') {
        isOpen.value = false;
        emit('close');
    } else if (e.key === 'ArrowDown' && productCount > 0) {
        e.preventDefault();
        isOpen.value = true;
        activeIndex.value = (activeIndex.value + 1) % productCount;
    } else if (e.key === 'ArrowUp' && productCount > 0) {
        e.preventDefault();
        activeIndex.value =
            activeIndex.value <= 0 ? productCount - 1 : activeIndex.value - 1;
    }
}

function onEnter() {
    const product = results.value.products[activeIndex.value];

    if (product) {
        selectProduct(product.slug, product.name);
    } else {
        submitFullSearch();
    }
}

const showDropdown = computed(() => {
    return (
        isOpen.value &&
        (query.value.trim().length >= 2 || recentSearches.value.length > 0)
    );
});
</script>

<template>
    <div
        ref="searchContainer"
        role="search"
        aria-label="Product search"
        class="relative w-full"
        @keydown="handleKeydown"
    >
        <div class="relative">
            <span
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-ink-subtle"
            >
                <SearchIcon
                    v-if="!isLoading"
                    class="size-4"
                    aria-hidden="true"
                />
                <LoaderCircleIcon
                    v-else
                    class="size-4 animate-spin"
                    aria-hidden="true"
                />
            </span>

            <input
                ref="inputRef"
                v-model="query"
                type="text"
                role="combobox"
                :aria-expanded="showDropdown"
                :aria-controls="listboxId"
                :aria-activedescendant="activeOptionId"
                aria-autocomplete="list"
                aria-label="Search products"
                :aria-busy="isLoading || undefined"
                :placeholder="placeholder"
                @focus="onInputFocus"
                @keydown.enter.prevent="onEnter"
                :class="
                    cn(
                        fieldSurfaceClasses,
                        fieldStateClasses(false),
                        'h-12 rounded-full pr-12 pl-12 text-base sm:text-sm',
                    )
                "
            />

            <button
                v-if="query"
                type="button"
                @click="query = ''"
                class="absolute inset-y-0 right-2 my-auto flex size-8 items-center justify-center rounded-full text-ink-subtle transition-colors hover:bg-surface-sunken hover:text-ink"
                aria-label="Clear search"
            >
                <XIcon class="size-4" aria-hidden="true" />
            </button>
        </div>

        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 -translate-y-1"
            leave-active-class="transition duration-100 ease-in"
            leave-to-class="opacity-0 -translate-y-1"
        >
            <div
                v-if="showDropdown"
                class="absolute top-full z-50 mt-2 w-full overflow-hidden rounded-2xl border border-border bg-surface-raised shadow-lg"
            >
                <!-- Recent searches -->
                <div
                    v-if="query.trim().length < 2 && recentSearches.length > 0"
                    class="flex flex-col gap-3 p-4"
                >
                    <div class="flex items-center justify-between gap-4">
                        <p
                            class="text-xs font-semibold tracking-wider text-ink-subtle uppercase"
                        >
                            Recent searches
                        </p>
                        <button
                            type="button"
                            aria-label="Clear recent searches"
                            @click="clearRecentSearches"
                            class="text-xs font-medium text-rose-700 hover:underline"
                        >
                            Clear
                        </button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Chip
                            v-for="term in recentSearches"
                            :key="term"
                            @click="submitFullSearch(term)"
                        >
                            <ClockIcon
                                class="size-3 text-ink-subtle"
                                aria-hidden="true"
                            />
                            {{ term }}
                        </Chip>
                    </div>
                </div>

                <SearchAutocompleteSkeleton
                    v-if="isLoading && query.trim().length >= 2"
                />

                <div v-else-if="query.trim().length >= 2" class="flex flex-col">
                    <!-- Matching categories -->
                    <div
                        v-if="
                            results.categories && results.categories.length > 0
                        "
                        class="flex flex-col gap-3 border-b border-border p-4"
                    >
                        <p
                            class="text-xs font-semibold tracking-wider text-ink-subtle uppercase"
                        >
                            Collections
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <Chip
                                v-for="cat in results.categories"
                                :key="cat.id"
                                @click="selectCategory(cat.slug, cat.name)"
                            >
                                {{ cat.name }}
                            </Chip>
                        </div>
                    </div>

                    <!-- Matching products -->
                    <div
                        v-if="results.products && results.products.length > 0"
                        class="flex flex-col"
                    >
                        <p
                            class="px-4 pt-4 pb-2 text-xs font-semibold tracking-wider text-ink-subtle uppercase"
                        >
                            Products
                        </p>
                        <ul
                            :id="listboxId"
                            role="listbox"
                            aria-label="Matching products"
                            class="max-h-80 overflow-y-auto pb-2"
                        >
                            <li
                                v-for="(prod, index) in results.products"
                                :id="`${listboxId}-${index}`"
                                :key="prod.id"
                                role="option"
                                :aria-selected="index === activeIndex"
                            >
                                <button
                                    type="button"
                                    tabindex="-1"
                                    @click="selectProduct(prod.slug, prod.name)"
                                    @mouseenter="activeIndex = index"
                                    :class="
                                        cn(
                                            'flex h-16 w-full items-center gap-4 px-4 text-left transition-colors',
                                            index === activeIndex
                                                ? 'bg-surface-sunken'
                                                : 'hover:bg-surface-sunken',
                                        )
                                    "
                                >
                                    <div
                                        class="size-12 shrink-0 overflow-hidden rounded-lg bg-surface-sunken"
                                    >
                                        <img
                                            v-if="prod.main_image"
                                            :src="
                                                resolveStorageUrl(
                                                    prod.main_image,
                                                )
                                            "
                                            :alt="prod.name"
                                            class="size-full object-cover"
                                            loading="lazy"
                                            @error="showPlaceholderOnError"
                                        />
                                    </div>
                                    <div
                                        class="flex min-w-0 flex-1 flex-col gap-1"
                                    >
                                        <p
                                            class="truncate text-sm font-medium text-ink"
                                        >
                                            {{ prod.name }}
                                        </p>
                                        <p
                                            class="flex items-center gap-2 text-xs text-ink-subtle"
                                        >
                                            <span
                                                class="font-semibold text-ink"
                                            >
                                                {{ formatCurrency(prod.price) }}
                                            </span>
                                            <span
                                                v-if="prod.category"
                                                class="truncate"
                                            >
                                                {{ prod.category.name }}
                                            </span>
                                            <span
                                                v-if="!prod.is_in_stock"
                                                class="text-warning-700"
                                            >
                                                Out of stock
                                            </span>
                                        </p>
                                    </div>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div
                        v-else-if="!isLoading"
                        class="flex flex-col gap-2 p-8 text-center"
                    >
                        <p class="text-sm font-medium text-ink">
                            No results for “{{ query }}”
                        </p>
                        <p class="text-xs text-ink-subtle">
                            Try <em>cleanser</em>, <em>serum</em> or
                            <em>lipstick</em>.
                        </p>
                    </div>

                    <div
                        v-if="results.total_matches > 0"
                        class="border-t border-border p-2"
                    >
                        <button
                            type="button"
                            @click="submitFullSearch()"
                            class="flex h-12 w-full items-center justify-center gap-2 rounded-lg text-sm font-medium text-rose-800 transition-colors hover:bg-surface-sunken"
                        >
                            View all {{ results.total_matches }} results
                            <ArrowRightIcon class="size-4" aria-hidden="true" />
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
