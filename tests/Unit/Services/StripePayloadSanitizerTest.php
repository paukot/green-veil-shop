<?php

use App\Services\Stripe\StripePayloadSanitizer;

describe('StripePayloadSanitizer', function () {
    it('summarizes checkout.session.completed event with card details and amount', function () {
        $sanitizer = new StripePayloadSanitizer;

        $payload = [
            'id' => 'evt_test_123',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_abc',
                    'amount_total' => 2989,
                    'currency' => 'eur',
                    'payment_status' => 'paid',
                    'payment_method_details' => [
                        'card' => [
                            'brand' => 'visa',
                            'last4' => '4242',
                            'exp_month' => 12,
                            'exp_year' => 2028,
                        ],
                    ],
                    'outcome' => [
                        'seller_message' => 'Payment complete.',
                    ],
                ],
            ],
        ];

        $summary = $sanitizer->summarize('checkout.session.completed', $payload);

        expect($summary['label'])->toBe('Checkout Completed');
        expect($summary['color'])->toBe('success');
        expect($summary['amount'])->toBe('€29.89');
        expect($summary['payment_method'])->toBe('Visa •••• 4242');
        expect($summary['status'])->toBe('paid');
        expect($summary['outcome_message'])->toBe('Payment complete.');
    });

    it('sanitizes sensitive data and removes IP, fingerprints, and full addresses', function () {
        $sanitizer = new StripePayloadSanitizer;

        $payload = [
            'id' => 'evt_test_sensitive',
            'type' => 'charge.succeeded',
            'created' => 1793616090,
            'data' => [
                'object' => [
                    'id' => 'ch_test_123',
                    'amount' => 4500,
                    'currency' => 'eur',
                    'client_ip' => '192.168.1.1',
                    'payment_intent' => 'pi_test_xyz',
                    'payment_method_details' => [
                        'card' => [
                            'brand' => 'mastercard',
                            'last4' => '5555',
                            'funding' => 'credit',
                            'fingerprint' => 'super_secret_fingerprint_xyz',
                            'country' => 'IE',
                            'exp_month' => 6,
                            'exp_year' => 2027,
                        ],
                    ],
                    'billing_details' => [
                        'address' => [
                            'line1' => '123 Private Residential Street',
                            'city' => 'Dublin',
                            'postal_code' => 'D02 X285',
                            'country' => 'IE',
                        ],
                    ],
                    'metadata' => [
                        'order_id' => '42',
                    ],
                ],
            ],
        ];

        $sanitized = $sanitizer->sanitize($payload);

        expect($sanitized['event_id'])->toBe('evt_test_sensitive');
        expect($sanitized['identifiers']['payment_intent_id'])->toBe('pi_test_xyz');
        expect($sanitized['payment_summary']['amount_formatted'])->toBe('€45.00');

        // Card summary kept
        expect($sanitized['card_details']['brand'])->toBe('Mastercard');
        expect($sanitized['card_details']['last4'])->toBe('5555');

        // Sensitive data scrubbed
        expect($sanitized)->not->toHaveKey('client_ip');
        expect($sanitized['card_details'])->not->toHaveKey('fingerprint');
        expect($sanitized)->not->toHaveKey('billing_details');
    });
});
