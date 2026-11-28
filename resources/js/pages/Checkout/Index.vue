<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    CheckboxField,
    RadioCard,
    RadioCardGroup,
    TextField,
    TextareaField,
} from '@/components/form';
import type { SelectOption } from '@/components/form';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import Breadcrumbs from '@/components/storefront/Breadcrumbs.vue';
import AddressFormFields from '@/components/storefront/checkout/AddressFormFields.vue';
import CheckoutOrderSummary from '@/components/storefront/checkout/CheckoutOrderSummary.vue';
import { formatCurrency } from '@/lib/utils';
import type {
    AppliedPromotionData,
    AvailablePromotionData,
    CheckoutPageProps,
    SavedAddressItem,
} from '@/types/storefront';
import { store as checkoutStore } from '@/actions/App/Http/Controllers/CheckoutController';
import { index as cartIndex } from '@/routes/cart';
import { login } from '@/routes';
import { useCart } from '@/composables/useCart';
import {
    ChevronDownIcon,
    PhoneIcon,
    PlusIcon,
    ShoppingBagIcon,
    TriangleAlertIcon,
} from '@lucide/vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import Container from '@/components/ui/Container.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import { FormSection } from '@/components/form';
import { cn } from '@/lib/utils';

defineOptions({ layout: StorefrontLayout });

const mobileSummaryOpen = ref(false);

const props = defineProps<CheckoutPageProps>();

const countryOptions = computed<SelectOption[]>(() => {
    if (props.countries && props.countries.length > 0) {
        return props.countries;
    }
    return [{ value: 'IE', label: 'Ireland' }];
});

const defaultDelivery =
    props.deliveryOptions.find((d) => d.is_free) ?? props.deliveryOptions[0];
const defaultPayment = props.paymentOptions[0];
const defaultCountry = props.countries?.[0]?.value ?? 'IE';

const defaultSavedAddress = computed<SavedAddressItem | null>(() => {
    if (!props.savedAddresses || props.savedAddresses.length === 0) {
        return null;
    }
    return (
        props.savedAddresses.find((a) => a.is_default) ??
        props.savedAddresses[0]
    );
});

const form = useForm({
    customer_name:
        defaultSavedAddress.value?.recipient_name ?? props.customer?.name ?? '',
    customer_email: props.customer?.email ?? '',
    customer_phone: defaultSavedAddress.value?.phone ?? '',
    delivery_option_id: defaultDelivery ? defaultDelivery.id : 0,
    payment_option_id: defaultPayment ? defaultPayment.id : 0,
    shipping_address: {
        line1: defaultSavedAddress.value?.line1 ?? '',
        line2: defaultSavedAddress.value?.line2 ?? '',
        city: defaultSavedAddress.value?.city ?? '',
        postal_code: defaultSavedAddress.value?.postal_code ?? '',
        country: defaultSavedAddress.value?.country ?? defaultCountry,
    },
    billing_same_as_shipping: true,
    billing_address: {
        line1: defaultSavedAddress.value?.line1 ?? '',
        line2: defaultSavedAddress.value?.line2 ?? '',
        city: defaultSavedAddress.value?.city ?? '',
        postal_code: defaultSavedAddress.value?.postal_code ?? '',
        country: defaultSavedAddress.value?.country ?? defaultCountry,
    },
    notes: '',
    selected_address_id: defaultSavedAddress.value?.id ?? null,
    save_address_to_profile: false,
    address_label: '',
});

function selectSavedAddress(address: SavedAddressItem) {
    form.selected_address_id = address.id;
    form.customer_name = address.recipient_name;
    if (address.phone) {
        form.customer_phone = address.phone;
    }
    form.shipping_address = {
        line1: address.line1,
        line2: address.line2 ?? '',
        city: address.city,
        postal_code: address.postal_code,
        country: address.country,
    };
    if (form.billing_same_as_shipping) {
        form.billing_address = { ...form.shipping_address };
    }
    form.save_address_to_profile = false;
    form.address_label = '';
}

function selectNewAddress() {
    form.selected_address_id = null;
    form.shipping_address = {
        line1: '',
        line2: '',
        city: '',
        postal_code: '',
        country: defaultCountry,
    };
    if (form.billing_same_as_shipping) {
        form.billing_address = { ...form.shipping_address };
    }
}

const selectedDelivery = computed(() => {
    return (
        props.deliveryOptions.find((d) => d.id === form.delivery_option_id) ??
        null
    );
});

const selectedPayment = computed(() => {
    return (
        props.paymentOptions.find((p) => p.id === form.payment_option_id) ??
        null
    );
});

const deliveryCost = computed(() => {
    if (!selectedDelivery.value) return 0;
    return selectedDelivery.value.is_free ? 0 : selectedDelivery.value.price;
});

const isApplyingPromo = ref(false);
const promoError = ref<string | null>(null);
const promoSuccess = ref<string | null>(null);

const cart = useCart();

onMounted(() => {
    cart.initFromServer(
        props.items as any,
        props.appliedPromotion,
        props.availablePromotions,
    );
});

const activePromo = computed<AppliedPromotionData | null>(() => {
    return cart.appliedPromotion.value ?? props.appliedPromotion ?? null;
});

const availablePromotions = computed<AvailablePromotionData[]>(() => {
    return cart.availablePromotions.value.length > 0
        ? cart.availablePromotions.value
        : (props.availablePromotions ?? []);
});

const discountAmount = computed(() => {
    return activePromo.value
        ? activePromo.value.discount_amount
        : (props.summary.discount_amount ?? 0);
});

const calculatedTotal = computed(() => {
    return Math.max(
        0,
        props.summary.subtotal - discountAmount.value + deliveryCost.value,
    );
});

const generalErrors = computed(
    () => form.errors as Record<string, string | undefined>,
);

async function handleApplyPromo(code: string) {
    if (!code || isApplyingPromo.value) return;
    promoError.value = null;
    promoSuccess.value = null;
    isApplyingPromo.value = true;

    const res = await cart.applyPromotion({ code });
    isApplyingPromo.value = false;

    if (res.success) {
        promoSuccess.value = res.message || 'Promotion applied!';
        setTimeout(() => {
            promoSuccess.value = null;
        }, 3500);
    } else {
        promoError.value = res.error || 'Invalid promotion code';
    }
}

async function handleApplyPromoOffer(promoId: number) {
    if (isApplyingPromo.value) return;
    promoError.value = null;
    promoSuccess.value = null;
    isApplyingPromo.value = true;

    const res = await cart.applyPromotion({ promotion_id: promoId });
    isApplyingPromo.value = false;

    if (res.success) {
        promoSuccess.value = res.message || 'Promotion applied!';
        setTimeout(() => {
            promoSuccess.value = null;
        }, 3500);
    } else {
        promoError.value = res.error || 'Failed to apply promotion';
    }
}

async function handleRemovePromo() {
    if (isApplyingPromo.value) return;
    promoError.value = null;
    promoSuccess.value = null;
    isApplyingPromo.value = true;

    await cart.removePromotion();
    isApplyingPromo.value = false;
}

const removeLocationListener = router.on('location', () => {
    cart.clearCart();
});

onUnmounted(() => {
    removeLocationListener();
});

function submit() {
    form.post(checkoutStore.url(), {
        preserveScroll: true,
        onSuccess: () => {
            cart.clearCart();
        },
    });
}
</script>

<template>
    <Head title="Secure Checkout" />

    <Container class="flex flex-col gap-8 py-8 lg:gap-12 lg:py-12">
        <div class="flex flex-col gap-4">
            <Breadcrumbs
                inline
                :items="[
                    { name: 'Bag', url: cartIndex.url() },
                    { name: 'Checkout', url: '' },
                ]"
            />
            <PageHeader title="Checkout">
                <template v-if="!customer" #actions>
                    <p class="text-sm text-ink-muted">
                        Have an account?
                        <Link
                            :href="login.url()"
                            class="font-medium text-rose-800 underline underline-offset-4"
                        >
                            Sign in
                        </Link>
                    </p>
                </template>
            </PageHeader>
        </div>

        <form
            class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12 lg:gap-12"
            aria-label="Checkout"
            @submit.prevent="submit"
        >
            <div class="flex min-w-0 flex-col gap-8 lg:col-span-7">
                <!-- Mobile order summary -->
                <Card padding="none" class="overflow-hidden lg:hidden">
                    <button
                        type="button"
                        class="flex h-16 w-full items-center justify-between gap-4 px-4"
                        aria-controls="checkout-mobile-summary-panel"
                        :aria-expanded="mobileSummaryOpen"
                        @click="mobileSummaryOpen = !mobileSummaryOpen"
                    >
                        <span
                            class="flex items-center gap-3 text-sm font-medium text-ink"
                        >
                            <ShoppingBagIcon
                                class="size-4 text-rose-700"
                                aria-hidden="true"
                            />
                            {{ mobileSummaryOpen ? 'Hide' : 'Show' }} order
                            summary
                            <ChevronDownIcon
                                :class="
                                    cn(
                                        'size-4 text-ink-subtle transition-transform',
                                        mobileSummaryOpen && 'rotate-180',
                                    )
                                "
                                aria-hidden="true"
                            />
                        </span>
                        <span
                            class="text-base font-semibold text-ink tabular-nums"
                        >
                            {{ formatCurrency(calculatedTotal) }}
                        </span>
                    </button>
                    <div
                        v-show="mobileSummaryOpen"
                        id="checkout-mobile-summary-panel"
                        class="border-t border-border p-4"
                    >
                        <CheckoutOrderSummary
                            id-prefix="mobile"
                            test-id-suffix="-mobile"
                            bare
                            :show-submit="false"
                            :items="items"
                            :summary="summary"
                            :applied-promotion="activePromo"
                            :available-promotions="availablePromotions"
                            :selected-delivery="selectedDelivery"
                            :selected-payment="selectedPayment"
                            :delivery-cost="deliveryCost"
                            :discount-amount="discountAmount"
                            :calculated-total="calculatedTotal"
                            :processing="form.processing"
                            :is-applying-promo="isApplyingPromo"
                            :promo-error="promoError"
                            :promo-success="promoSuccess"
                            @apply-promo="handleApplyPromo"
                            @apply-offer="handleApplyPromoOffer"
                            @remove-promo="handleRemovePromo"
                        />
                    </div>
                </Card>

                <div
                    v-if="generalErrors.payment || generalErrors.cart"
                    class="flex items-start gap-3 rounded-2xl border border-danger-200 bg-danger-50 p-4 text-sm text-danger-700"
                    role="alert"
                >
                    <TriangleAlertIcon
                        class="mt-1 size-4 shrink-0"
                        aria-hidden="true"
                    />
                    <div class="flex flex-col gap-1">
                        <p v-if="generalErrors.payment">
                            {{ generalErrors.payment }}
                        </p>
                        <p v-if="generalErrors.cart">
                            {{ generalErrors.cart }}
                        </p>
                    </div>
                </div>

                <Card padding="lg">
                    <FormSection :step="1" title="Contact">
                        <TextField
                            id="customer_name"
                            v-model="form.customer_name"
                            label="Full name"
                            required
                            placeholder="Lady Catherine"
                            :error="form.errors.customer_name"
                        />
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField
                                id="customer_email"
                                v-model="form.customer_email"
                                type="email"
                                label="Email"
                                required
                                placeholder="catherine@example.com"
                                :error="form.errors.customer_email"
                            />
                            <TextField
                                id="customer_phone"
                                v-model="form.customer_phone"
                                type="tel"
                                label="Phone"
                                subtext="for delivery updates"
                                placeholder="+353 87 123 4567"
                                :error="form.errors.customer_phone"
                            />
                        </div>
                    </FormSection>
                </Card>

                <Card padding="lg">
                    <FormSection :step="2" title="Shipping address">
                        <div
                            v-if="savedAddresses && savedAddresses.length > 0"
                            class="flex flex-col gap-3"
                            role="group"
                            aria-label="Saved shipping addresses"
                        >
                            <p
                                class="flex items-baseline justify-between gap-4 text-sm"
                            >
                                <span class="font-medium text-ink"
                                    >Saved addresses</span
                                >
                                <span class="text-xs text-ink-subtle">
                                    {{ savedAddresses.length }} of 3 saved
                                </span>
                            </p>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <button
                                    v-for="address in savedAddresses"
                                    :key="address.id"
                                    type="button"
                                    :aria-label="`${address.label} — ${address.recipient_name}, ${address.formatted}`"
                                    :aria-pressed="
                                        form.selected_address_id === address.id
                                    "
                                    :class="
                                        cn(
                                            'flex h-full flex-col gap-2 rounded-lg border p-4 text-left transition-colors',
                                            form.selected_address_id ===
                                                address.id
                                                ? 'border-rose-700 bg-rose-50 ring-1 ring-rose-700'
                                                : 'border-border-strong bg-surface-raised hover:border-cream-400',
                                        )
                                    "
                                    @click="selectSavedAddress(address)"
                                >
                                    <span
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <Badge>{{ address.label }}</Badge>
                                        <Badge
                                            v-if="address.is_default"
                                            tone="brand"
                                        >
                                            Default
                                        </Badge>
                                    </span>
                                    <span class="text-sm font-medium text-ink">
                                        {{ address.recipient_name }}
                                    </span>
                                    <span
                                        class="line-clamp-2 text-xs text-ink-muted"
                                    >
                                        {{ address.formatted }}
                                    </span>
                                    <span
                                        v-if="address.phone"
                                        class="mt-auto flex items-center gap-2 text-xs text-ink-subtle"
                                    >
                                        <PhoneIcon
                                            class="size-3"
                                            aria-hidden="true"
                                        />
                                        {{ address.phone }}
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    :aria-pressed="
                                        form.selected_address_id === null
                                    "
                                    :class="
                                        cn(
                                            'flex h-full min-h-32 flex-col items-center justify-center gap-2 rounded-lg border border-dashed p-4 text-center transition-colors',
                                            form.selected_address_id === null
                                                ? 'border-rose-700 bg-rose-50'
                                                : 'border-border-strong hover:border-cream-400',
                                        )
                                    "
                                    @click="selectNewAddress"
                                >
                                    <span
                                        class="flex size-8 items-center justify-center rounded-full bg-surface-sunken text-ink"
                                    >
                                        <PlusIcon
                                            class="size-4"
                                            aria-hidden="true"
                                        />
                                    </span>
                                    <span class="text-sm font-medium text-ink">
                                        Use a new address
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="form.selected_address_id !== null"
                            class="flex flex-col gap-4 rounded-lg bg-surface-sunken p-4 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div class="flex flex-col gap-1 text-sm">
                                <p class="font-medium text-ink">
                                    {{ form.customer_name }}
                                </p>
                                <p class="text-ink-muted">
                                    {{ form.shipping_address.line1
                                    }}<template
                                        v-if="form.shipping_address.line2"
                                        >,
                                        {{
                                            form.shipping_address.line2
                                        }}</template
                                    >, {{ form.shipping_address.city }},
                                    {{ form.shipping_address.postal_code }}
                                    ({{ form.shipping_address.country }})
                                </p>
                                <p
                                    v-if="form.customer_phone"
                                    class="text-xs text-ink-subtle"
                                >
                                    Delivery updates to
                                    {{ form.customer_phone }}
                                </p>
                            </div>
                            <Button
                                size="sm"
                                variant="outline"
                                @click="selectNewAddress"
                            >
                                Use a different address
                            </Button>
                        </div>

                        <div v-else class="flex flex-col gap-4">
                            <AddressFormFields
                                id-prefix="shipping"
                                error-prefix="shipping_address"
                                v-model="form.shipping_address"
                                :country-options="countryOptions"
                                :errors="form.errors"
                            />

                            <div
                                v-if="customer && canSaveAddress"
                                class="flex flex-col gap-4 rounded-lg bg-surface-sunken p-4"
                            >
                                <CheckboxField
                                    id="save_address_to_profile"
                                    v-model="form.save_address_to_profile"
                                    label="Save this address to my account"
                                    description="Keep up to 3 addresses for faster checkout."
                                />
                                <TextField
                                    v-if="form.save_address_to_profile"
                                    id="address_label"
                                    v-model="form.address_label"
                                    label="Label"
                                    placeholder="Home, Office, Studio"
                                    subtext="optional"
                                    class="max-w-xs"
                                    :error="form.errors.address_label"
                                />
                            </div>
                        </div>

                        <div
                            class="flex flex-col gap-4 border-t border-border pt-6"
                        >
                            <CheckboxField
                                id="billing_same_as_shipping"
                                v-model="form.billing_same_as_shipping"
                                label="Billing address is the same as shipping"
                            />
                            <div
                                v-if="!form.billing_same_as_shipping"
                                class="flex flex-col gap-4"
                            >
                                <h3 class="text-sm font-medium text-ink">
                                    Billing address
                                </h3>
                                <AddressFormFields
                                    id-prefix="billing"
                                    error-prefix="billing_address"
                                    v-model="form.billing_address"
                                    :country-options="countryOptions"
                                    :errors="form.errors"
                                />
                            </div>
                        </div>
                    </FormSection>
                </Card>

                <Card padding="lg">
                    <FormSection :step="3" title="Delivery">
                        <RadioCardGroup
                            aria-label="Delivery method"
                            :error="form.errors.delivery_option_id"
                        >
                            <RadioCard
                                v-for="option in deliveryOptions"
                                :key="option.id"
                                v-model="form.delivery_option_id"
                                :value="option.id"
                                name="delivery_option"
                                :title="option.name"
                                :subtitle="option.estimated_delivery_label"
                                :description="option.description"
                            >
                                <template #trailing>
                                    <Badge
                                        v-if="
                                            option.is_free ||
                                            Number(option.price) === 0
                                        "
                                        tone="success"
                                        >Free</Badge
                                    >
                                    <span
                                        v-else
                                        class="text-sm font-semibold text-ink tabular-nums"
                                    >
                                        {{ formatCurrency(option.price) }}
                                    </span>
                                </template>
                            </RadioCard>
                        </RadioCardGroup>
                    </FormSection>
                </Card>

                <Card padding="lg">
                    <FormSection :step="4" title="Payment">
                        <RadioCardGroup
                            aria-label="Payment method"
                            :error="form.errors.payment_option_id"
                        >
                            <RadioCard
                                v-for="option in paymentOptions"
                                :key="option.id"
                                v-model="form.payment_option_id"
                                :value="option.id"
                                name="payment_option"
                                align="start"
                                :title="option.name"
                                :description="option.description"
                            >
                                <template #badge>
                                    <Badge
                                        v-if="option.driver === 'stripe'"
                                        tone="brand"
                                    >
                                        Card · Apple Pay
                                    </Badge>
                                </template>
                            </RadioCard>
                        </RadioCardGroup>

                        <TextareaField
                            id="order_notes"
                            v-model="form.notes"
                            label="Order notes"
                            subtext="optional"
                            :rows="3"
                            placeholder="Delivery instructions or anything we should know…"
                            :error="form.errors.notes"
                        />
                    </FormSection>
                </Card>

                <Button
                    type="submit"
                    size="lg"
                    block
                    class="lg:hidden"
                    :loading="form.processing"
                >
                    {{
                        selectedPayment?.driver === 'stripe'
                            ? 'Continue to payment'
                            : 'Place order'
                    }}
                    · {{ formatCurrency(calculatedTotal) }}
                </Button>
            </div>

            <div class="hidden lg:col-span-5 lg:block">
                <CheckoutOrderSummary
                    id-prefix="desktop"
                    test-id-suffix="-desktop"
                    :items="items"
                    :summary="summary"
                    :applied-promotion="activePromo"
                    :available-promotions="availablePromotions"
                    :selected-delivery="selectedDelivery"
                    :selected-payment="selectedPayment"
                    :delivery-cost="deliveryCost"
                    :discount-amount="discountAmount"
                    :calculated-total="calculatedTotal"
                    :processing="form.processing"
                    :is-applying-promo="isApplyingPromo"
                    :promo-error="promoError"
                    :promo-success="promoSuccess"
                    @apply-promo="handleApplyPromo"
                    @apply-offer="handleApplyPromoOffer"
                    @remove-promo="handleRemovePromo"
                />
            </div>
        </form>
    </Container>
</template>
