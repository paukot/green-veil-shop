<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    CheckboxField,
    SelectField,
    TextField,
    type SelectOption,
} from '@/components/form';
import Button from '@/components/ui/Button.vue';
import AccountLayout from '@/layouts/AccountLayout.vue';
import type { SavedAddressItem } from '@/types/storefront';
import {
    store as storeAddressRoute,
    update as updateAddressRoute,
    destroy as destroyAddressRoute,
    defaultMethod as setDefaultAddressRoute,
} from '@/routes/account/addresses';

defineOptions({ layout: AccountLayout });

interface AddressesProps {
    addresses: SavedAddressItem[];
    countries: SelectOption[];
    maxAddresses: number;
    canAddAddress: boolean;
}

const props = defineProps<AddressesProps>();

const isFormOpen = ref(false);
const editingAddressId = ref<number | null>(null);
const deletingAddress = ref<SavedAddressItem | null>(null);

const defaultCountry = String(props.countries?.[0]?.value ?? 'IE');

interface AddressFormData {
    label: string;
    recipient_name: string;
    phone: string;
    line1: string;
    line2: string;
    city: string;
    postal_code: string;
    country: string;
    is_default: boolean;
    address_limit?: string;
}

const form = useForm<AddressFormData>({
    label: 'Home',
    recipient_name: '',
    phone: '',
    line1: '',
    line2: '',
    city: '',
    postal_code: '',
    country: defaultCountry,
    is_default: false,
});

const labelPresets = ['Home', 'Work', 'Studio', 'Other'];

function selectPresetLabel(preset: string) {
    form.label = preset;
}

function openCreateForm() {
    editingAddressId.value = null;
    form.reset();
    form.clearErrors();
    form.label = 'Home';
    form.country = defaultCountry;
    form.is_default = props.addresses.length === 0;
    isFormOpen.value = true;
}

function openEditForm(address: SavedAddressItem) {
    editingAddressId.value = address.id;
    form.clearErrors();
    form.label = address.label;
    form.recipient_name = address.recipient_name;
    form.phone = address.phone ?? '';
    form.line1 = address.line1;
    form.line2 = address.line2 ?? '';
    form.city = address.city;
    form.postal_code = address.postal_code;
    form.country = address.country;
    form.is_default = address.is_default;
    isFormOpen.value = true;
}

function closeForm() {
    isFormOpen.value = false;
    editingAddressId.value = null;
    form.reset();
    form.clearErrors();
}

function submitForm() {
    if (editingAddressId.value !== null) {
        form.put(updateAddressRoute.url({ address: editingAddressId.value }), {
            preserveScroll: true,
            onSuccess: () => closeForm(),
        });
    } else {
        form.post(storeAddressRoute.url(), {
            preserveScroll: true,
            onSuccess: () => closeForm(),
        });
    }
}

function confirmDelete(address: SavedAddressItem) {
    deletingAddress.value = address;
}

function cancelDelete() {
    deletingAddress.value = null;
}

function executeDelete() {
    if (!deletingAddress.value) return;

    router.delete(
        destroyAddressRoute.url({ address: deletingAddress.value.id }),
        {
            preserveScroll: true,
            onSuccess: () => {
                deletingAddress.value = null;
            },
        },
    );
}

function setDefaultAddress(address: SavedAddressItem) {
    router.patch(
        setDefaultAddressRoute.url({ address: address.id }),
        {},
        {
            preserveScroll: true,
        },
    );
}

const formTitle = computed(() => {
    return editingAddressId.value
        ? 'Edit Shipping Address'
        : 'Add New Shipping Address';
});
</script>

<template>
    <Head title="Saved Shipping Addresses — Green Veil" />

    <div class="space-y-8">
        <!-- Header & Action section -->
        <section
            class="rounded-2xl border border-cream-200 bg-white p-6 shadow-xs sm:p-8"
        >
            <div
                class="flex flex-col gap-4 border-b border-cream-200 pb-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <div class="flex items-center gap-3">
                        <h2
                            class="font-serif text-xl text-sage-900 sm:text-2xl"
                        >
                            Saved Shipping Addresses
                        </h2>
                        <span
                            class="inline-flex items-center rounded-full bg-sage-100 px-2.5 py-0.5 text-xs font-semibold text-sage-800"
                        >
                            {{ addresses.length }} / {{ maxAddresses }} Saved
                        </span>
                    </div>
                    <p class="text-charcoal-600 mt-1 text-xs">
                        Save up to {{ maxAddresses }} shipping destinations with
                        contact numbers for fast, one-click checkout.
                    </p>
                </div>

                <div class="shrink-0">
                    <Button
                        v-if="!isFormOpen"
                        type="button"
                        @click="openCreateForm"
                        :disabled="!canAddAddress"
                        size="sm"
                    >
                        <span class="mr-1">+</span> Add Address
                    </Button>
                </div>
            </div>

            <!-- Address limit warning if limit reached -->
            <div
                v-if="!canAddAddress && !isFormOpen"
                class="text-charcoal-600 mt-4 flex items-center justify-between rounded-xl border border-cream-200 bg-cream-50/70 p-3 text-xs"
            >
                <span
                    >You have reached the maximum of {{ maxAddresses }} saved
                    addresses. Edit or remove an existing address to save a new
                    one.</span
                >
            </div>

            <!-- Add / Edit Inline Modal Drawer Form -->
            <div
                v-if="isFormOpen"
                class="mt-6 rounded-2xl border border-sage-200 bg-sage-50/40 p-6"
            >
                <div
                    class="mb-5 flex items-center justify-between border-b border-sage-200 pb-3"
                >
                    <h3 class="font-serif text-lg text-sage-900">
                        {{ formTitle }}
                    </h3>
                    <button
                        type="button"
                        @click="closeForm"
                        class="text-charcoal-500 text-xs underline hover:text-charcoal-800"
                    >
                        Cancel
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="max-w-2xl space-y-4">
                    <!-- Label selection -->
                    <div>
                        <label
                            class="mb-2 block text-xs font-semibold tracking-wider text-charcoal-700 uppercase"
                        >
                            Address Label
                        </label>
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            <button
                                v-for="preset in labelPresets"
                                :key="preset"
                                type="button"
                                @click="selectPresetLabel(preset)"
                                class="rounded-full px-3 py-1 text-xs font-medium transition-colors"
                                :class="
                                    form.label === preset
                                        ? 'bg-sage-800 text-cream-50'
                                        : 'border border-cream-200 bg-white text-charcoal-700 hover:bg-cream-100'
                                "
                            >
                                {{ preset }}
                            </button>
                        </div>
                        <TextField
                            id="label"
                            v-model="form.label"
                            placeholder="Custom Label (e.g. Parents, Beach House)"
                            :error="form.errors.label"
                        />
                    </div>

                    <!-- Recipient & Phone -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <TextField
                            id="recipient_name"
                            v-model="form.recipient_name"
                            label="Recipient Full Name"
                            required
                            placeholder="Jane Doe"
                            :error="form.errors.recipient_name"
                        />

                        <TextField
                            id="phone"
                            v-model="form.phone"
                            type="tel"
                            label="Phone Number"
                            subtext="(for delivery SMS notifications)"
                            placeholder="+353 87 123 4567"
                            :error="form.errors.phone"
                        />
                    </div>

                    <!-- Street lines -->
                    <TextField
                        id="line1"
                        v-model="form.line1"
                        label="Address Line 1"
                        required
                        placeholder="42 Willowbrook Lane"
                        :error="form.errors.line1"
                    />

                    <TextField
                        id="line2"
                        v-model="form.line2"
                        label="Address Line 2"
                        subtext="(optional)"
                        placeholder="Apt 3B"
                        :error="form.errors.line2"
                    />

                    <!-- City, Postal, Country -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <TextField
                            id="city"
                            v-model="form.city"
                            label="City"
                            required
                            placeholder="Dublin"
                            :error="form.errors.city"
                        />

                        <TextField
                            id="postal_code"
                            v-model="form.postal_code"
                            label="Postal Code / Eircode"
                            required
                            placeholder="D02 X285"
                            :error="form.errors.postal_code"
                        />

                        <SelectField
                            id="country"
                            v-model="form.country"
                            label="Country"
                            required
                            :options="countries"
                            :error="form.errors.country"
                        />
                    </div>

                    <!-- Default address checkbox -->
                    <div class="pt-2">
                        <CheckboxField
                            id="is_default"
                            v-model="form.is_default"
                            label="Set as default shipping address"
                        />
                    </div>

                    <!-- General errors -->
                    <div
                        v-if="form.errors.address_limit"
                        class="text-xs font-medium text-rose-600"
                    >
                        {{ form.errors.address_limit }}
                    </div>

                    <!-- Action buttons -->
                    <div class="flex items-center gap-3 pt-3">
                        <Button
                            type="submit"
                            :loading="form.processing"
                            size="md"
                        >
                            {{ form.processing ? 'Saving...' : 'Save Address' }}
                        </Button>
                        <Button
                            type="button"
                            variant="secondary"
                            @click="closeForm"
                            size="md"
                        >
                            Cancel
                        </Button>
                    </div>
                </form>
            </div>

            <!-- Existing Addresses Grid -->
            <div v-if="addresses.length > 0" class="mt-6">
                <div
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="address in addresses"
                        :key="address.id"
                        class="relative flex flex-col justify-between rounded-2xl border p-5 transition-all"
                        :class="
                            address.is_default
                                ? 'border-sage-300 bg-sage-50/50 shadow-xs'
                                : 'border-cream-200 bg-white hover:border-cream-300'
                        "
                    >
                        <!-- Card Header -->
                        <div>
                            <div
                                class="mb-2 flex items-center justify-between gap-2"
                            >
                                <span
                                    class="inline-flex items-center rounded-full bg-cream-200/90 px-2.5 py-0.5 text-[11px] font-semibold text-charcoal-800"
                                >
                                    {{ address.label }}
                                </span>
                                <span
                                    v-if="address.is_default"
                                    class="inline-flex items-center gap-1 rounded-full bg-sage-200 px-2.5 py-0.5 text-[10px] font-semibold text-sage-900"
                                >
                                    ✓ Default
                                </span>
                            </div>

                            <h4
                                class="font-serif text-base font-medium text-charcoal-900"
                            >
                                {{ address.recipient_name }}
                            </h4>

                            <p
                                v-if="address.phone"
                                class="text-charcoal-600 mt-1 flex items-center gap-1.5 text-xs"
                            >
                                <span class="text-charcoal-400">📞</span>
                                <span>{{ address.phone }}</span>
                            </p>

                            <p
                                class="mt-2 text-xs leading-relaxed text-charcoal-700"
                            >
                                {{ address.line1
                                }}<template v-if="address.line2"
                                    >, {{ address.line2 }}</template
                                ><br />
                                {{ address.city }}, {{ address.postal_code
                                }}<br />
                                <span class="font-medium text-sage-800">{{
                                    address.country_name || address.country
                                }}</span>
                            </p>
                        </div>

                        <!-- Card Actions -->
                        <div
                            class="mt-5 flex flex-wrap items-center justify-between gap-2 border-t border-cream-100 pt-3"
                        >
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="openEditForm(address)"
                                    class="text-xs font-medium text-sage-800 underline hover:text-sage-900"
                                >
                                    Edit
                                </button>
                                <span class="text-charcoal-300">•</span>
                                <button
                                    type="button"
                                    @click="confirmDelete(address)"
                                    class="text-xs font-medium text-rose-600 underline hover:text-rose-700"
                                >
                                    Delete
                                </button>
                            </div>

                            <button
                                v-if="!address.is_default"
                                type="button"
                                @click="setDefaultAddress(address)"
                                class="text-charcoal-600 text-[11px] font-medium transition-colors hover:text-sage-900"
                            >
                                Set as Default
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else-if="!isFormOpen"
                class="mt-6 flex flex-col items-center justify-center rounded-2xl border border-dashed border-cream-200 bg-cream-50/40 p-8 text-center"
            >
                <span
                    class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-cream-200/80 text-xl text-sage-800"
                >
                    📍
                </span>
                <h3 class="font-serif text-base text-charcoal-900">
                    No Shipping Addresses Saved Yet
                </h3>
                <p class="text-charcoal-500 mt-1 max-w-sm text-xs">
                    Save your primary delivery locations and contact numbers for
                    seamless 1-click checkout.
                </p>
                <div class="mt-4">
                    <Button type="button" @click="openCreateForm" size="sm">
                        Add Your First Address
                    </Button>
                </div>
            </div>
        </section>

        <!-- Delete Confirmation Modal -->
        <div
            v-if="deletingAddress"
            class="fixed inset-0 z-50 flex items-center justify-center bg-charcoal-950/40 p-4 backdrop-blur-xs"
        >
            <div
                class="animate-fade-in w-full max-w-md rounded-2xl border border-cream-200 bg-white p-6 shadow-xl"
            >
                <h3 class="font-serif text-lg text-charcoal-900">
                    Remove Shipping Address?
                </h3>
                <p class="text-charcoal-600 mt-2 text-xs leading-relaxed">
                    Are you sure you want to remove
                    <span class="font-semibold text-charcoal-900"
                        >"{{ deletingAddress.label }}"</span
                    >
                    ({{ deletingAddress.formatted }})? This action cannot be
                    undone.
                </p>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <Button
                        type="button"
                        variant="secondary"
                        size="sm"
                        @click="cancelDelete"
                    >
                        Cancel
                    </Button>
                    <button
                        type="button"
                        @click="executeDelete"
                        class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-rose-700"
                    >
                        Remove Address
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
