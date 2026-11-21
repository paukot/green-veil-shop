import type { BadgeTone } from '@/components/ui/Badge.vue';
import { formatCurrency } from '@/lib/utils';

/**
 * Format status strings with underscores into clean capitalized title text.
 * e.g. "pending_payment" -> "Pending Payment"
 */
export function formatStatusLabel(status: string): string {
    if (!status) return '';
    return status
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

/**
 * Badge tone for an order fulfilment status.
 */
export function getOrderStatusTone(status: string): BadgeTone {
    switch (status) {
        case 'paid':
        case 'delivered':
        case 'completed':
            return 'success';
        case 'processing':
        case 'shipped':
            return 'brand';
        case 'pending_payment':
        case 'pending':
            return 'warning';
        case 'cancelled':
        case 'refunded':
        default:
            return 'neutral';
    }
}

/**
 * Badge tone for a payment lifecycle status.
 */
export function getPaymentStatusTone(paymentStatus: string): BadgeTone {
    switch (paymentStatus) {
        case 'paid':
            return 'success';
        case 'unpaid':
            return 'warning';
        case 'partially_refunded':
        case 'refunded':
            return 'accent';
        case 'failed':
        case 'expired':
            return 'danger';
        default:
            return 'neutral';
    }
}

/**
 * "10%" or "€5": whole amounts drop their decimals, which read as noise in offers.
 */
export function formatDiscount(
    discountType: string,
    discountValue: number | string,
): string {
    const value = Number(discountValue);

    if (discountType === 'percentage') {
        return `${Number.isInteger(value) ? value : value.toFixed(1)}%`;
    }

    return formatCurrency(value).replace(/[.,]00(?=\D*$)/, '');
}
