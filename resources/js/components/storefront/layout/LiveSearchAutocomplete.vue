<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { formatCurrency, resolveStorageUrl } from '@/lib/utils';
import { index as productsIndex } from '@/routes/products';
import { show as categoryShow } from '@/actions/App/Http/Controllers/CategoryController';
import {
    search as productSearch,
    show as productShow,
} from '@/actions/App/Http/Controllers/ProductController';
import type { ProductCardData } from '@/types/storefront';

const props = withDefaults(
    defineProps<{
        placeholder?: string;
        size?: 'sm' | 'md' | 'lg';
        autofocus?: boolean;
    }>(),
    {
        placeholder: 'Search botanicals, formulations, concerns...',
        size: 'md',
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

    document.addEventListener('click', handleOutsideClick);
});

onUnmounted(() => {
    document.removeEventListener('click', handleOutsideClick);
    if (debounceTimeout) clearTimeout(debounceTimeout);
    if (abortController) abortController.abort();
});

function handleOutsideClick(event: MouseEvent) {
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

    const trimmed = val.trim();
    if (trimmed.length < 2) {
        results.value = { products: [], categories: [], total_matches: 0 };
        isLoading.value = false;
        return;
    }

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
    if (e.key === 'Escape') {
        isOpen.value = false;
        emit('close');
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
    <div ref="searchContainer" class="relative w-full" @keydown="handleKeydown">
        <!-- Search Input Field -->
        <div class="relative flex items-center">
            <span
                class="pointer-events-none absolute left-3.5 flex items-center text-sage-600"
            >
                <svg
                    v-if="!isLoading"
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"
                    />
                </svg>
                <svg
                    v-else
                    class="h-4 w-4 animate-spin text-sage-600"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    />
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                    />
                </svg>
            </span>

            <input
                ref="inputRef"
                v-model="query"
                type="text"
                role="combobox"
                :aria-expanded="showDropdown"
                aria-autocomplete="list"
                aria-haspopup="listbox"
                :placeholder="placeholder"
                @focus="onInputFocus"
                @keydown.enter.prevent="submitFullSearch()"
                class="placeholder-charcoal-400 w-full rounded-full border border-cream-300 bg-white/90 pr-9 pl-10 text-xs text-charcoal-900 shadow-2xs transition-all duration-200 focus:border-sage-500 focus:bg-white focus:ring-2 focus:ring-sage-500/20 focus:outline-none sm:text-sm"
                :class="{
                    'py-2': size === 'sm',
                    'py-2.5': size === 'md',
                    'py-3 text-base': size === 'lg',
                }"
            />

            <!-- Clear button -->
            <button
                v-if="query"
                type="button"
                @click="query = ''"
                class="text-charcoal-400 absolute right-3 hover:text-charcoal-700 focus:outline-none"
                aria-label="Clear search input"
            >
                <svg
                    class="h-3.5 w-3.5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>

        <!-- Live Autocomplete Panel -->
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 translate-y-1 scale-98"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-1 scale-98"
        >
            <div
                v-if="showDropdown"
                class="absolute top-full z-50 mt-2 w-full overflow-hidden rounded-2xl border border-cream-200 bg-white/98 shadow-xl backdrop-blur-md"
            >
                <!-- State 1: Empty Query -> Recent Searches & Popular Tags -->
                <div
                    v-if="query.trim().length < 2 && recentSearches.length > 0"
                    class="p-4"
                >
                    <div class="mb-2 flex items-center justify-between text-xs">
                        <span
                            class="text-charcoal-600 font-semibold tracking-wider uppercase"
                        >
                            Recent Searches
                        </span>
                        <button
                            type="button"
                            @click="clearRecentSearches"
                            class="text-[11px] text-sage-700 hover:underline"
                        >
                            Clear
                        </button>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="term in recentSearches"
                            :key="term"
                            type="button"
                            @click="submitFullSearch(term)"
                            class="inline-flex items-center gap-1.5 rounded-full border border-cream-200 bg-cream-50 px-3 py-1 text-xs text-charcoal-800 transition-colors hover:border-sage-300 hover:bg-sage-50 hover:text-sage-900"
                        >
                            <svg
                                class="text-charcoal-400 h-3 w-3"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            <span>{{ term }}</span>
                        </button>
                    </div>
                </div>

                <!-- State 2: Active Results -->
                <div
                    v-else-if="query.trim().length >= 2"
                    class="divide-y divide-cream-100"
                >
                    <!-- Matching Categories if any -->
                    <div
                        v-if="
                            results.categories && results.categories.length > 0
                        "
                        class="bg-cream-50/50 p-3"
                    >
                        <p
                            class="text-charcoal-500 mb-2 text-[10px] font-semibold tracking-wider uppercase"
                        >
                            Botanical Collections
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="cat in results.categories"
                                :key="cat.id"
                                type="button"
                                @click="selectCategory(cat.slug, cat.name)"
                                class="hover:border-sage-400 inline-flex items-center gap-1 rounded-lg border border-cream-200 bg-white px-2.5 py-1 text-xs font-medium text-sage-900 transition-colors hover:bg-sage-50"
                            >
                                <span>🌿 {{ cat.name }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Matching Products -->
                    <div
                        v-if="results.products && results.products.length > 0"
                        class="max-h-80 divide-y divide-cream-100 overflow-y-auto"
                    >
                        <div
                            class="sticky top-0 bg-white/95 px-4 py-2 backdrop-blur-xs"
                        >
                            <span
                                class="text-charcoal-500 text-[10px] font-semibold tracking-wider uppercase"
                            >
                                Matching Formulas
                            </span>
                        </div>
                        <button
                            v-for="prod in results.products"
                            :key="prod.id"
                            type="button"
                            @click="selectProduct(prod.slug, prod.name)"
                            class="flex w-full items-center gap-3 p-3 text-left transition-colors hover:bg-cream-50/80"
                        >
                            <div
                                class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border border-cream-200 bg-cream-100"
                            >
                                <img
                                    v-if="prod.main_image"
                                    :src="resolveStorageUrl(prod.main_image)"
                                    :alt="prod.name"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="text-charcoal-400 flex h-full w-full items-center justify-center text-sm"
                                >
                                    🍃
                                </div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <h4
                                        class="truncate text-xs font-medium text-charcoal-900"
                                    >
                                        {{ prod.name }}
                                    </h4>
                                    <span
                                        v-if="prod.category"
                                        class="text-charcoal-400 hidden text-[10px] sm:inline"
                                    >
                                        • {{ prod.category.name }}
                                    </span>
                                </div>
                                <div
                                    class="mt-0.5 flex items-center gap-2 text-[11px]"
                                >
                                    <span class="font-bold text-sage-900">
                                        {{ formatCurrency(prod.price) }}
                                    </span>
                                    <span
                                        v-if="prod.volume"
                                        class="text-charcoal-500"
                                    >
                                        {{ prod.volume }}
                                        {{ prod.unit || 'ml' }}
                                    </span>
                                    <span
                                        v-if="!prod.is_in_stock"
                                        class="text-[10px] font-semibold text-amber-700"
                                    >
                                        (Out of stock)
                                    </span>
                                </div>
                            </div>
                            <svg
                                class="text-charcoal-300 h-4 w-4 shrink-0"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M8.25 4.5l7.5 7.5-7.5 7.5"
                                />
                            </svg>
                        </button>
                    </div>

                    <!-- No Results Found -->
                    <div v-else-if="!isLoading" class="p-6 text-center text-xs">
                        <p class="font-medium text-charcoal-800">
                            No botanical formulas found for "{{ query }}"
                        </p>
                        <p class="text-charcoal-500 mt-1 text-[11px]">
                            Try searching for <em>cleanser</em>, <em>serum</em>,
                            or <em>lavender</em>.
                        </p>
                    </div>

                    <!-- View All Results Footer -->
                    <div
                        v-if="results.total_matches > 0"
                        class="bg-cream-50/60 p-2.5 text-center"
                    >
                        <button
                            type="button"
                            @click="submitFullSearch()"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-sage-800 hover:text-sage-950 hover:underline"
                        >
                            <span>
                                View all {{ results.total_matches }} formulas
                                matching "{{ query }}"
                            </span>
                            <span>→</span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
