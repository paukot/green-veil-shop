<?php

namespace App\Services\Stripe;

class StripePayloadSanitizer
{
    /**
     * Generate an audit-friendly, human-readable summary of a Stripe event payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array{
     *     type: string,
     *     label: string,
     *     color: string,
     *     icon: string,
     *     amount: string|null,
     *     payment_method: string|null,
     *     status: string|null,
     *     outcome_message: string|null,
     *     failure_message: string|null,
     * }
     */
    public function summarize(string $type, array $payload): array
    {
        $dataObject = $payload['data']['object'] ?? [];

        $amountCents = $dataObject['amount_total']
            ?? $dataObject['amount']
            ?? $dataObject['amount_refunded']
            ?? null;

        $currency = strtoupper((string) ($dataObject['currency'] ?? 'EUR'));

        $formattedAmount = ($amountCents !== null)
            ? '€'.number_format(((int) $amountCents) / 100, 2)
            : null;

        $paymentMethod = $this->extractPaymentMethodSummary($dataObject);
        $status = $dataObject['payment_status'] ?? $dataObject['status'] ?? null;
        $outcomeMessage = $dataObject['outcome']['seller_message'] ?? null;
        $failureMessage = $dataObject['last_payment_error']['message']
            ?? $dataObject['failure_message']
            ?? null;

        [$label, $color, $icon] = match ($type) {
            'checkout.session.completed' => [
                'Checkout Completed',
                ($status === 'paid' ? 'success' : 'info'),
                'heroicon-o-check-circle',
            ],
            'checkout.session.async_payment_succeeded', 'payment_intent.succeeded', 'charge.succeeded' => [
                'Payment Succeeded',
                'success',
                'heroicon-o-check-badge',
            ],
            'checkout.session.async_payment_failed', 'payment_intent.payment_failed', 'charge.failed' => [
                'Payment Failed',
                'danger',
                'heroicon-o-x-circle',
            ],
            'checkout.session.expired' => [
                'Session Expired',
                'gray',
                'heroicon-o-clock',
            ],
            'charge.refunded' => [
                'Refund Processed',
                'warning',
                'heroicon-o-arrow-uturn-left',
            ],
            default => [
                str_replace(['.', '_'], ' ', ucwords($type, '._')),
                'gray',
                'heroicon-o-information-circle',
            ],
        };

        return [
            'type' => $type,
            'label' => $label,
            'color' => $color,
            'icon' => $icon,
            'amount' => $formattedAmount,
            'payment_method' => $paymentMethod,
            'status' => $status,
            'outcome_message' => $outcomeMessage,
            'failure_message' => $failureMessage,
        ];
    }

    /**
     * Sanitize raw Stripe payload to safely inspect in admin UI without leaking customer PII or raw secrets.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sanitize(array $payload): array
    {
        $dataObject = $payload['data']['object'] ?? [];
        $eventId = $payload['id'] ?? null;
        $eventType = $payload['type'] ?? null;
        $createdAt = isset($payload['created']) ? date('Y-m-d H:i:s', (int) $payload['created']).' UTC' : null;

        $amountCents = $dataObject['amount_total']
            ?? $dataObject['amount']
            ?? $dataObject['amount_refunded']
            ?? null;

        $currency = strtoupper((string) ($dataObject['currency'] ?? 'EUR'));

        $sanitized = [
            'event_id' => $eventId,
            'event_type' => $eventType,
            'stripe_created_at' => $createdAt,
            'identifiers' => array_filter([
                'session_id' => $dataObject['id'] ?? null,
                'payment_intent_id' => $dataObject['payment_intent'] ?? null,
                'charge_id' => $dataObject['latest_charge'] ?? $dataObject['charge'] ?? null,
            ]),
            'payment_summary' => array_filter([
                'payment_status' => $dataObject['payment_status'] ?? null,
                'status' => $dataObject['status'] ?? null,
                'amount_formatted' => $amountCents !== null ? '€'.number_format(((int) $amountCents) / 100, 2) : null,
                'amount_cents' => $amountCents,
                'currency' => $currency,
            ]),
            'card_details' => $this->extractCardDetails($dataObject),
            'outcome' => array_filter([
                'seller_message' => $dataObject['outcome']['seller_message'] ?? null,
                'network_status' => $dataObject['outcome']['network_status'] ?? null,
                'risk_level' => $dataObject['outcome']['risk_level'] ?? null,
                'risk_score' => $dataObject['outcome']['risk_score'] ?? null,
            ]),
            'failure' => array_filter([
                'code' => $dataObject['last_payment_error']['code'] ?? $dataObject['failure_code'] ?? null,
                'message' => $dataObject['last_payment_error']['message'] ?? $dataObject['failure_message'] ?? null,
                'decline_code' => $dataObject['last_payment_error']['decline_code'] ?? null,
            ]),
            'metadata' => array_filter($dataObject['metadata'] ?? []),
        ];

        return array_filter($sanitized, fn ($val) => ! empty($val) || $val === 0);
    }

    /**
     * @param  array<string, mixed>  $dataObject
     */
    protected function extractPaymentMethodSummary(array $dataObject): ?string
    {
        $card = $dataObject['payment_method_details']['card']
            ?? $dataObject['customer_details']['card']
            ?? null;

        if (is_array($card) && ! empty($card['last4'])) {
            $brand = ucfirst((string) ($card['brand'] ?? 'Card'));

            return "{$brand} •••• {$card['last4']}";
        }

        $type = $dataObject['payment_method_types'][0]
            ?? $dataObject['payment_method_details']['type']
            ?? null;

        return $type ? ucfirst((string) $type) : null;
    }

    /**
     * @param  array<string, mixed>  $dataObject
     * @return array<string, mixed>|null
     */
    protected function extractCardDetails(array $dataObject): ?array
    {
        $card = $dataObject['payment_method_details']['card'] ?? null;

        if (! is_array($card) || empty($card['last4'])) {
            return null;
        }

        return array_filter([
            'brand' => ucfirst((string) ($card['brand'] ?? '')),
            'last4' => $card['last4'],
            'funding' => ucfirst((string) ($card['funding'] ?? '')),
            'country' => $card['country'] ?? null,
            'exp_month' => $card['exp_month'] ?? null,
            'exp_year' => $card['exp_year'] ?? null,
            'three_d_secure' => is_array($card['three_d_secure'] ?? null)
                ? ($card['three_d_secure']['result'] ?? 'authenticated')
                : null,
        ]);
    }
}
