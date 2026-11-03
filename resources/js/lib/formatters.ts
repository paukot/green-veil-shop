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
 * Returns Tailwind badge classes corresponding to an order fulfillment status.
 */
export function getOrderStatusClass(status: string): string {
    switch (status) {
        case 'paid':
        case 'delivered':
        case 'completed':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'processing':
        case 'shipped':
            return 'bg-sage-50 text-sage-700 border-sage-200';
        case 'pending_payment':
        case 'pending':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'cancelled':
        case 'refunded':
            return 'bg-stone-100 text-stone-600 border-stone-200';
        default:
            return 'bg-cream-100 text-charcoal-600 border-cream-300';
    }
}

/**
 * Returns Tailwind badge classes corresponding to a payment lifecycle status.
 */
export function getPaymentStatusClass(paymentStatus: string): string {
    switch (paymentStatus) {
        case 'paid':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'unpaid':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'partially_refunded':
        case 'refunded':
            return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'failed':
        case 'expired':
            return 'bg-rose-50 text-rose-700 border-rose-200';
        default:
            return 'bg-cream-100 text-charcoal-600 border-cream-300';
    }
}
