<?php

namespace Database\Seeders;

use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\DeliveryOption;
use App\Models\PaymentOption;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    /**
     * Curated European addresses for realistic shipping and billing data.
     *
     * @var list<array{line1: string, city: string, postal_code: string, country: string}>
     */
    protected const ADDRESSES = [
        ['line1' => 'ul. Marszałkowska 10/12', 'city' => 'Warsaw', 'postal_code' => '00-590', 'country' => 'PL'],
        ['line1' => 'ul. Floriańska 24', 'city' => 'Kraków', 'postal_code' => '31-021', 'country' => 'PL'],
        ['line1' => 'ul. Piotrkowska 85', 'city' => 'Łódź', 'postal_code' => '90-423', 'country' => 'PL'],
        ['line1' => 'Friedrichstraße 43', 'city' => 'Berlin', 'postal_code' => '10117', 'country' => 'DE'],
        ['line1' => 'Maximilianstraße 12', 'city' => 'Munich', 'postal_code' => '80539', 'country' => 'DE'],
        ['line1' => 'Boulevard Saint-Germain 152', 'city' => 'Paris', 'postal_code' => '75006', 'country' => 'FR'],
        ['line1' => 'Rue de Rivoli 88', 'city' => 'Paris', 'postal_code' => '75004', 'country' => 'FR'],
        ['line1' => 'Via Monte Napoleone 8', 'city' => 'Milan', 'postal_code' => '20121', 'country' => 'IT'],
        ['line1' => 'Via del Corso 114', 'city' => 'Rome', 'postal_code' => '00186', 'country' => 'IT'],
        ['line1' => 'Calle de Serrano 38', 'city' => 'Madrid', 'postal_code' => '28001', 'country' => 'ES'],
        ['line1' => 'Passeig de Gràcia 76', 'city' => 'Barcelona', 'postal_code' => '08008', 'country' => 'ES'],
        ['line1' => 'Herengracht 340', 'city' => 'Amsterdam', 'postal_code' => '1016 CG', 'country' => 'NL'],
        ['line1' => 'Grafton Street 45', 'city' => 'Dublin', 'postal_code' => 'D02 K674', 'country' => 'IE'],
        ['line1' => 'Avenue Louise 120', 'city' => 'Brussels', 'postal_code' => '1050', 'country' => 'BE'],
        ['line1' => 'Kärntner Straße 18', 'city' => 'Vienna', 'postal_code' => '1010', 'country' => 'AT'],
    ];

    /**
     * Guest customers fallback pool.
     *
     * @var list<array{name: string, email: string, phone: string}>
     */
    protected const GUEST_CUSTOMERS = [
        ['name' => 'Clara Lindqvist', 'email' => 'clara.lindqvist@example.se', 'phone' => '+46701234567'],
        ['name' => 'Matteo Rossi', 'email' => 'matteo.rossi@example.it', 'phone' => '+39021234567'],
        ['name' => 'Camille Laurent', 'email' => 'camille.laurent@example.fr', 'phone' => '+33140123456'],
        ['name' => 'Lukas Weber', 'email' => 'lukas.weber@example.de', 'phone' => '+49301234567'],
        ['name' => 'Sofia Garcia', 'email' => 'sofia.garcia@example.es', 'phone' => '+34911234567'],
        ['name' => 'Zofia Kowalska', 'email' => 'zofia.kowalska@example.pl', 'phone' => '+48601234567'],
        ['name' => 'Emma van Dijk', 'email' => 'emma.vandijk@example.nl', 'phone' => '+31201234567'],
        ['name' => 'Liam O’Connor', 'email' => 'liam.oconnor@example.ie', 'phone' => '+35312345678'],
    ];

    public function run(): void
    {
        DB::disableQueryLog();

        $users = User::query()->get();
        $adminUser = $users->firstWhere('email', 'admin@example.com');
        $customerUser = $users->firstWhere('email', 'customer@example.com');

        $deliveryOptions = DeliveryOption::query()->get()->keyBy('code');
        $standardDelivery = $deliveryOptions->get('standard') ?? $deliveryOptions->first();
        $expressDelivery = $deliveryOptions->get('express') ?? $standardDelivery;
        $pickupDelivery = $deliveryOptions->get('pickup') ?? $standardDelivery;

        $paymentOptions = PaymentOption::query()->get()->keyBy('code');
        $stripeOption = $paymentOptions->get('stripe') ?? $paymentOptions->first();
        $bankOption = $paymentOptions->get('bank_transfer');
        $codOption = $paymentOptions->get('cod');

        $promotions = Promotion::query()->get();

        // Sample 300 products to construct realistic orders quickly
        $products = Product::query()
            ->select(['id', 'name', 'sku', 'price', 'main_image'])
            ->take(300)
            ->get();

        if ($products->isEmpty()) {
            return;
        }

        $orderCount = 200;
        $orderItemsBatch = [];
        $paymentsBatch = [];
        $stripeEventsBatch = [];

        for ($i = 1; $i <= $orderCount; $i++) {
            // First order is the deterministic demo order for consistency
            $isDemo = ($i === 1);
            $number = $isDemo ? 'GV-DEMO-0001' : sprintf('GV-%06d', 1000 + $i);
            $publicId = (string) Str::ulid();

            // Customer resolution
            if ($isDemo) {
                $user = $adminUser;
                $customerName = 'Admin User';
                $customerEmail = 'admin@example.com';
                $customerPhone = '+48123456789';
            } elseif ($i === 2 && $customerUser) {
                $user = $customerUser;
                $customerName = $customerUser->name;
                $customerEmail = $customerUser->email;
                $customerPhone = '+48987654321';
            } else {
                $isGuest = ($i % 6 === 0);
                if ($isGuest || $users->isEmpty()) {
                    $guest = self::GUEST_CUSTOMERS[$i % count(self::GUEST_CUSTOMERS)];
                    $user = null;
                    $customerName = $guest['name'];
                    $customerEmail = $guest['email'];
                    $customerPhone = $guest['phone'];
                } else {
                    $user = $users[$i % $users->count()];
                    $customerName = $user->name;
                    $customerEmail = $user->email;
                    $customerPhone = '+48'.(500000000 + ($i * 739) % 400000000);
                }
            }

            // Addresses
            $address = self::ADDRESSES[$i % count(self::ADDRESSES)];
            $shippingAddress = $address;
            $billingAddress = $address;

            // Delivery Option selection
            $delivPick = $i % 10;
            $deliveryOption = match (true) {
                $delivPick < 6 => $standardDelivery,
                $delivPick < 8 => $expressDelivery,
                default => $pickupDelivery,
            };

            // Payment Option selection: 75% Stripe, 15% Bank Transfer, 10% COD
            $payPick = $i % 100;
            $paymentOption = match (true) {
                $payPick < 75 => $stripeOption,
                $payPick < 90 => ($bankOption ?? $stripeOption),
                default => ($codOption ?? $stripeOption),
            };

            // Timestamps: spread across last 90 days with chronologically realistic spread
            // Demo order is 5 days ago; others spread from 88 days ago to 1 hour ago
            $minutesAgo = $isDemo ? (5 * 24 * 60) : max(60, (int) (($orderCount - $i) * (88 * 24 * 60 / $orderCount) + (($i * 37) % 720)));
            $placedAt = Carbon::now()->subMinutes($minutesAgo);

            // Order Status & Payment Status
            if ($isDemo) {
                $status = OrderStatus::Processing;
                $paymentStatus = PaymentStatus::Paid;
            } else {
                $statusPick = $i % 100;
                if ($statusPick < 55) {
                    $status = OrderStatus::Completed;
                    $paymentStatus = PaymentStatus::Paid;
                } elseif ($statusPick < 75) {
                    $status = OrderStatus::Processing;
                    $paymentStatus = PaymentStatus::Paid;
                } elseif ($statusPick < 88) {
                    $status = OrderStatus::Shipped;
                    $paymentStatus = PaymentStatus::Paid;
                } elseif ($statusPick < 95) {
                    $status = OrderStatus::PendingPayment;
                    $paymentStatus = PaymentStatus::Unpaid;
                } else {
                    $status = OrderStatus::Cancelled;
                    $paymentStatus = PaymentStatus::Failed;
                }
            }

            $paidAt = ($paymentStatus === PaymentStatus::Paid)
                ? $placedAt->copy()->addMinutes(max(1, ($i * 3) % 15))
                : null;

            // Items selection: 1 to 4 items
            $itemCount = ($i % 4) + 1;
            $orderItems = [];
            $subtotal = 0.0;

            for ($k = 0; $k < $itemCount; $k++) {
                $productIndex = (($i * 7) + ($k * 13)) % $products->count();
                $product = $products[$productIndex];
                $quantity = (($i + $k) % 3) + 1;
                $lineSubtotal = round($product->price * $quantity, 2);
                $subtotal += $lineSubtotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'product_image' => $product->main_image,
                    'unit_price' => $product->price,
                    'quantity' => $quantity,
                    'subtotal' => $lineSubtotal,
                    'created_at' => $placedAt->format('Y-m-d H:i:s'),
                    'updated_at' => $placedAt->format('Y-m-d H:i:s'),
                ];
            }

            $subtotal = round($subtotal, 2);

            // Promotions: 30% of orders have promotion
            $hasPromo = ($i % 3 === 0 && $promotions->isNotEmpty());
            $promotion = $hasPromo ? $promotions[$i % $promotions->count()] : null;
            $discountAmount = 0.0;

            if ($promotion) {
                if ($promotion->discount_type === DiscountType::Percentage) {
                    $discountAmount = round($subtotal * ((float) $promotion->discount_value / 100), 2);
                } else {
                    $discountAmount = min((float) $promotion->discount_value, $subtotal);
                }
            }

            // Delivery amount
            $deliveryAmount = 0.0;
            if ($deliveryOption && $deliveryOption->price > 0) {
                if ($deliveryOption->free_threshold !== null && $subtotal >= (float) $deliveryOption->free_threshold) {
                    $deliveryAmount = 0.0;
                } else {
                    $deliveryAmount = (float) $deliveryOption->price;
                }
            }

            $total = round(max(0.0, $subtotal - $discountAmount + $deliveryAmount), 2);

            // Stripe Identifiers
            $isStripe = ($paymentOption->driver ?? '') === 'stripe';
            $stripeSessionId = $isStripe ? 'cs_test_'.Str::random(24) : null;
            $stripePaymentIntentId = $isStripe ? 'pi_test_'.Str::random(24) : null;
            $stripeChargeId = ($isStripe && $paymentStatus === PaymentStatus::Paid) ? 'ch_test_'.Str::random(24) : null;

            // Insert Order
            $orderId = DB::table('orders')->insertGetId([
                'public_id' => $publicId,
                'number' => $number,
                'user_id' => $user?->id,
                'delivery_option_id' => $deliveryOption?->id,
                'payment_option_id' => $paymentOption?->id,
                'promotion_id' => $promotion?->id,
                'status' => $status->value,
                'payment_status' => $paymentStatus->value,
                'stripe_session_id' => $stripeSessionId,
                'stripe_payment_intent_id' => $stripePaymentIntentId,
                'stripe_charge_id' => $stripeChargeId,
                'subtotal' => $subtotal,
                'delivery_amount' => $deliveryAmount,
                'discount_amount' => $discountAmount,
                'tax_amount' => 0.00,
                'total' => $total,
                'currency' => 'EUR',
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
                'shipping_address' => json_encode($shippingAddress),
                'billing_address' => json_encode($billingAddress),
                'notes' => $isDemo ? 'Demo order with complete payment and audit logs.' : null,
                'placed_at' => $placedAt->format('Y-m-d H:i:s'),
                'paid_at' => $paidAt?->format('Y-m-d H:i:s'),
                'created_at' => $placedAt->format('Y-m-d H:i:s'),
                'updated_at' => ($paidAt ?? $placedAt)->format('Y-m-d H:i:s'),
            ]);

            // Add Order Items to batch
            foreach ($orderItems as $item) {
                $item['order_id'] = $orderId;
                $orderItemsBatch[] = $item;
            }

            // Payment record
            $paymentsBatch[] = [
                'order_id' => $orderId,
                'driver' => $paymentOption->driver ?? 'stripe',
                'stripe_session_id' => $stripeSessionId,
                'stripe_payment_intent_id' => $stripePaymentIntentId,
                'status' => $paymentStatus->value,
                'amount' => $total,
                'currency' => 'EUR',
                'failure_reason' => ($paymentStatus === PaymentStatus::Failed ? 'Card was declined.' : null),
                'paid_at' => $paidAt?->format('Y-m-d H:i:s'),
                'created_at' => $placedAt->format('Y-m-d H:i:s'),
                'updated_at' => ($paidAt ?? $placedAt)->format('Y-m-d H:i:s'),
            ];

            // Stripe Event Logs in ascending chronological order
            if ($isStripe && $stripeSessionId && $stripePaymentIntentId) {
                $totalCents = (int) round($total * 100);
                $cardBrands = ['Visa', 'Mastercard', 'Amex'];
                $cardBrand = $cardBrands[$i % count($cardBrands)];
                $cardLast4 = sprintf('%04d', 1000 + ($i * 73) % 9000);

                // Step 1: checkout.session.created
                $t1 = $placedAt->copy();
                $stripeEventsBatch[] = [
                    'order_id' => $orderId,
                    'stripe_event_id' => 'evt_'.Str::random(24),
                    'type' => 'checkout.session.created',
                    'processed_at' => $t1->format('Y-m-d H:i:s'),
                    'payload' => json_encode([
                        'id' => 'evt_'.Str::random(24),
                        'type' => 'checkout.session.created',
                        'data' => [
                            'object' => [
                                'id' => $stripeSessionId,
                                'amount_total' => $totalCents,
                                'currency' => 'eur',
                                'payment_status' => 'unpaid',
                                'status' => 'open',
                                'payment_method_types' => ['card'],
                            ],
                        ],
                    ]),
                    'created_at' => $t1->format('Y-m-d H:i:s'),
                    'updated_at' => $t1->format('Y-m-d H:i:s'),
                ];

                // Step 2: payment_intent.created
                $t2 = $placedAt->copy()->addSeconds(12);
                $stripeEventsBatch[] = [
                    'order_id' => $orderId,
                    'stripe_event_id' => 'evt_'.Str::random(24),
                    'type' => 'payment_intent.created',
                    'processed_at' => $t2->format('Y-m-d H:i:s'),
                    'payload' => json_encode([
                        'id' => 'evt_'.Str::random(24),
                        'type' => 'payment_intent.created',
                        'data' => [
                            'object' => [
                                'id' => $stripePaymentIntentId,
                                'amount' => $totalCents,
                                'currency' => 'eur',
                                'status' => 'requires_payment_method',
                            ],
                        ],
                    ]),
                    'created_at' => $t2->format('Y-m-d H:i:s'),
                    'updated_at' => $t2->format('Y-m-d H:i:s'),
                ];

                if ($paymentStatus === PaymentStatus::Paid) {
                    // Step 3: payment_intent.succeeded
                    $t3 = $placedAt->copy()->addSeconds(28);
                    $stripeEventsBatch[] = [
                        'order_id' => $orderId,
                        'stripe_event_id' => 'evt_'.Str::random(24),
                        'type' => 'payment_intent.succeeded',
                        'processed_at' => $t3->format('Y-m-d H:i:s'),
                        'payload' => json_encode([
                            'id' => 'evt_'.Str::random(24),
                            'type' => 'payment_intent.succeeded',
                            'data' => [
                                'object' => [
                                    'id' => $stripePaymentIntentId,
                                    'amount' => $totalCents,
                                    'currency' => 'eur',
                                    'status' => 'succeeded',
                                    'payment_method_details' => [
                                        'type' => 'card',
                                        'card' => [
                                            'brand' => $cardBrand,
                                            'last4' => $cardLast4,
                                            'funding' => 'credit',
                                            'country' => 'IE',
                                        ],
                                    ],
                                    'outcome' => [
                                        'network_status' => 'approved_by_network',
                                        'seller_message' => 'Payment complete.',
                                    ],
                                ],
                            ],
                        ]),
                        'created_at' => $t3->format('Y-m-d H:i:s'),
                        'updated_at' => $t3->format('Y-m-d H:i:s'),
                    ];

                    // Step 4: charge.succeeded
                    $t4 = $placedAt->copy()->addSeconds(29);
                    $stripeEventsBatch[] = [
                        'order_id' => $orderId,
                        'stripe_event_id' => 'evt_'.Str::random(24),
                        'type' => 'charge.succeeded',
                        'processed_at' => $t4->format('Y-m-d H:i:s'),
                        'payload' => json_encode([
                            'id' => 'evt_'.Str::random(24),
                            'type' => 'charge.succeeded',
                            'data' => [
                                'object' => [
                                    'id' => $stripeChargeId ?? ('ch_'.Str::random(24)),
                                    'amount' => $totalCents,
                                    'currency' => 'eur',
                                    'status' => 'succeeded',
                                    'payment_method_details' => [
                                        'type' => 'card',
                                        'card' => [
                                            'brand' => $cardBrand,
                                            'last4' => $cardLast4,
                                            'funding' => 'credit',
                                            'country' => 'IE',
                                        ],
                                    ],
                                    'outcome' => [
                                        'network_status' => 'approved_by_network',
                                        'seller_message' => 'Payment complete.',
                                    ],
                                ],
                            ],
                        ]),
                        'created_at' => $t4->format('Y-m-d H:i:s'),
                        'updated_at' => $t4->format('Y-m-d H:i:s'),
                    ];

                    // Step 5: checkout.session.completed
                    $t5 = $placedAt->copy()->addSeconds(32);
                    $stripeEventsBatch[] = [
                        'order_id' => $orderId,
                        'stripe_event_id' => 'evt_'.Str::random(24),
                        'type' => 'checkout.session.completed',
                        'processed_at' => $t5->format('Y-m-d H:i:s'),
                        'payload' => json_encode([
                            'id' => 'evt_'.Str::random(24),
                            'type' => 'checkout.session.completed',
                            'data' => [
                                'object' => [
                                    'id' => $stripeSessionId,
                                    'amount_total' => $totalCents,
                                    'currency' => 'eur',
                                    'payment_status' => 'paid',
                                    'status' => 'complete',
                                    'payment_method_details' => [
                                        'type' => 'card',
                                        'card' => [
                                            'brand' => $cardBrand,
                                            'last4' => $cardLast4,
                                        ],
                                    ],
                                ],
                            ],
                        ]),
                        'created_at' => $t5->format('Y-m-d H:i:s'),
                        'updated_at' => $t5->format('Y-m-d H:i:s'),
                    ];
                } elseif ($paymentStatus === PaymentStatus::Failed) {
                    // Step 3: payment_intent.payment_failed
                    $t3 = $placedAt->copy()->addSeconds(25);
                    $stripeEventsBatch[] = [
                        'order_id' => $orderId,
                        'stripe_event_id' => 'evt_'.Str::random(24),
                        'type' => 'payment_intent.payment_failed',
                        'processed_at' => $t3->format('Y-m-d H:i:s'),
                        'payload' => json_encode([
                            'id' => 'evt_'.Str::random(24),
                            'type' => 'payment_intent.payment_failed',
                            'data' => [
                                'object' => [
                                    'id' => $stripePaymentIntentId,
                                    'amount' => $totalCents,
                                    'currency' => 'eur',
                                    'status' => 'requires_payment_method',
                                    'last_payment_error' => [
                                        'message' => 'The card was declined or has insufficient funds.',
                                    ],
                                ],
                            ],
                        ]),
                        'created_at' => $t3->format('Y-m-d H:i:s'),
                        'updated_at' => $t3->format('Y-m-d H:i:s'),
                    ];
                }
            }
        }

        // Chunk insert items, payments, and events
        foreach (array_chunk($orderItemsBatch, 500) as $chunk) {
            DB::table('order_items')->insert($chunk);
        }

        foreach (array_chunk($paymentsBatch, 500) as $chunk) {
            DB::table('payments')->insert($chunk);
        }

        foreach (array_chunk($stripeEventsBatch, 500) as $chunk) {
            DB::table('stripe_events')->insert($chunk);
        }
    }
}
