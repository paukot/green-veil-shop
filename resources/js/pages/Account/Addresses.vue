<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    CheckboxField,
    SelectField,
    TextField,
    type SelectOption,
} from '@/components/form';
import { MapPinIcon, PhoneIcon, PlusIcon } from '@lucide/vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import Chip from '@/components/ui/Chip.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import Modal from '@/components/ui/Modal.vue';
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
    <Head title="Saved Shipping Addresses" />

    <section class="flex flex-col gap-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div class="flex flex-col gap-2">
                <h2
                    class="flex items-baseline gap-3 font-serif text-3xl text-ink"
                >
                    Addresses
                    <span class="font-sans text-sm text-ink-subtle">
                        {{ addresses.length }} of {{ maxAddresses }}
                    </span>
                </h2>
                <p class="text-sm text-ink-muted">
                    Save up to {{ maxAddresses }} delivery addresses for faster
                    checkout.
                </p>
            </div>
            <Button :disabled="!canAddAddress" @click="openCreateForm">
                <template #icon>
                    <PlusIcon class="size-4" aria-hidden="true" />
                </template>
                Add address
            </Button>
        </div>

        <p
            v-if="!canAddAddress"
            class="rounded-lg bg-surface-sunken p-4 text-sm text-ink-muted"
        >
            You've saved the maximum of {{ maxAddresses }} addresses. Edit or
            remove one to add another.
        </p>

        <div
            v-if="addresses.length > 0"
            class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3"
        >
            <Card
                v-for="address in addresses"
                :key="address.id"
                :class="
                    address.is_default
                        ? 'flex flex-col gap-4 border-sage-300 ring-1 ring-sage-300'
                        : 'flex flex-col gap-4'
                "
            >
                <div class="flex flex-wrap items-center gap-2">
                    <Badge>{{ address.label }}</Badge>
                    <Badge v-if="address.is_default" tone="brand"
                        >Default</Badge
                    >
                </div>
                <address
                    class="flex flex-col gap-1 text-sm text-ink-muted not-italic"
                >
                    <span class="font-serif text-lg text-ink">{{
                        address.recipient_name
                    }}</span>
                    <span>
                        {{ address.line1
                        }}<template v-if="address.line2"
                            >, {{ address.line2 }}</template
                        >
                    </span>
                    <span>{{ address.city }}, {{ address.postal_code }}</span>
                    <span>{{ address.country_name || address.country }}</span>
                    <span
                        v-if="address.phone"
                        class="flex items-center gap-2 pt-1"
                    >
                        <PhoneIcon class="size-3" aria-hidden="true" />
                        {{ address.phone }}
                    </span>
                </address>
                <div
                    class="mt-auto flex flex-wrap items-center gap-2 border-t border-border pt-4"
                >
                    <Button
                        size="sm"
                        variant="outline"
                        @click="openEditForm(address)"
                    >
                        Edit
                    </Button>
                    <Button
                        v-if="!address.is_default"
                        size="sm"
                        variant="ghost"
                        @click="setDefaultAddress(address)"
                    >
                        Make default
                    </Button>
                    <Button
                        size="sm"
                        variant="ghost"
                        class="ml-auto text-danger-600 hover:bg-danger-50"
                        @click="confirmDelete(address)"
                    >
                        Remove
                    </Button>
                </div>
            </Card>
        </div>

        <EmptyState
            v-else
            title="No saved addresses"
            description="Save where you usually receive deliveries for one-click checkout."
        >
            <template #icon>
                <MapPinIcon aria-hidden="true" />
            </template>
            <template #actions>
                <Button @click="openCreateForm">Add your first address</Button>
            </template>
        </EmptyState>
    </section>

    <Modal :open="isFormOpen" :title="formTitle" size="lg" @close="closeForm">
        <form
            id="address-form"
            class="flex flex-col gap-4"
            @submit.prevent="submitForm"
        >
            <div class="flex flex-col gap-3">
                <p class="text-sm font-medium text-ink">Label</p>
                <div class="flex flex-wrap gap-2">
                    <Chip
                        v-for="preset in labelPresets"
                        :key="preset"
                        :selected="form.label === preset"
                        @click="selectPresetLabel(preset)"
                    >
                        {{ preset }}
                    </Chip>
                </div>
                <TextField
                    id="label"
                    v-model="form.label"
                    placeholder="Or type your own, e.g. Parents"
                    aria-label="Address label"
                    :error="form.errors.label"
                />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <TextField
                    id="recipient_name"
                    v-model="form.recipient_name"
                    label="Recipient"
                    required
                    placeholder="Jane Doe"
                    :error="form.errors.recipient_name"
                />
                <TextField
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    label="Phone"
                    subtext="for delivery updates"
                    placeholder="+353 87 123 4567"
                    :error="form.errors.phone"
                />
            </div>
            <TextField
                id="line1"
                v-model="form.line1"
                label="Address line 1"
                required
                placeholder="42 Willowbrook Lane"
                :error="form.errors.line1"
            />
            <TextField
                id="line2"
                v-model="form.line2"
                label="Address line 2"
                subtext="optional"
                placeholder="Apt 3B"
                :error="form.errors.line2"
            />
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
                    label="Postal code"
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
            <CheckboxField
                id="is_default"
                v-model="form.is_default"
                label="Use as my default address"
            />
            <p
                v-if="form.errors.address_limit"
                class="text-sm text-danger-600"
                role="alert"
            >
                {{ form.errors.address_limit }}
            </p>
        </form>

        <template #footer>
            <Button variant="ghost" @click="closeForm">Cancel</Button>
            <Button
                type="submit"
                form="address-form"
                :loading="form.processing"
            >
                Save address
            </Button>
        </template>
    </Modal>

    <Modal
        :open="Boolean(deletingAddress)"
        title="Remove this address?"
        :description="
            deletingAddress
                ? `“${deletingAddress.label}” (${deletingAddress.formatted}) will be removed. This can't be undone.`
                : undefined
        "
        size="sm"
        @close="cancelDelete"
    >
        <template #footer>
            <Button variant="ghost" @click="cancelDelete">Keep it</Button>
            <Button variant="danger" @click="executeDelete"
                >Remove address</Button
            >
        </template>
    </Modal>
</template>
