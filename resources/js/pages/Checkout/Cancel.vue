<script setup lang="ts">
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import Container from '@/components/ui/Container.vue';
import { Head, Link } from '@inertiajs/vue3';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { formatCurrency } from '@/lib/utils';
import type { CheckoutCancelPageProps } from '@/types/storefront';
import { retry as checkoutRetry } from '@/actions/App/Http/Controllers/CheckoutResultController';
import { index as cartIndex } from '@/routes/cart';
import { CircleAlertIcon } from '@lucide/vue';

defineOptions({ layout: StorefrontLayout });

defineProps<CheckoutCancelPageProps>();
</script>

<template>
    <Head :title="`Order ${order.number} Cancelled`" />

    <Container size="narrow" class="py-16 lg:py-24">
        <Card padding="lg" class="flex flex-col items-center gap-6 text-center">
            <span
                class="flex size-16 items-center justify-center rounded-full bg-warning-50 text-warning-700"
            >
                <CircleAlertIcon class="size-6" aria-hidden="true" />
            </span>
            <div class="flex flex-col gap-3">
                <p
                    class="text-xs font-semibold tracking-widest text-warning-700 uppercase"
                >
                    Payment incomplete
                </p>
                <h1 class="font-serif text-4xl tracking-tight text-ink">
                    Your order wasn't finalised
                </h1>
                <p class="max-w-md text-sm text-ink-muted">
                    No payment was taken for order
                    <span class="font-mono font-semibold text-ink">{{
                        order.number
                    }}</span>
                    ({{ formatCurrency(order.total) }}). Your items are still
                    reserved if you'd like to try again or choose another way to
                    pay.
                </p>
            </div>
            <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
                <Link
                    :href="checkoutRetry.url(order.public_id)"
                    method="post"
                    as="button"
                    class="inline-flex h-12 items-center justify-center rounded-full bg-sage-900 px-8 text-xs font-semibold tracking-wider text-cream-50 uppercase transition-colors hover:bg-sage-800"
                >
                    Try payment again
                </Link>
                <Button :href="cartIndex.url()" variant="outline" size="lg">
                    Return to bag
                </Button>
            </div>
        </Card>
    </Container>
</template>
