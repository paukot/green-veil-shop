<?php

use App\Enums\DiscountType;
use App\Models\DeliveryOption;
use App\Models\PaymentOption;
use App\Models\Product;
use App\Models\Promotion;
use Inertia\Testing\AssertableInertia as Assert;

test('guest can apply valid promotion code to cart', function () {
    $product = Product::factory()->create([
        'price' => 50.00,
        'is_active' => true,
    ]);

    $promo = Promotion::factory()->create([
        'code' => 'BOTANICAL15',
        'discount_type' => DiscountType::Percentage,
        'discount_value' => 15.00,
        'minimum_order_amount' => null,
        'applies_to_all_products' => true,
        'is_active' => true,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addWeek(),
    ]);

    // Put item in guest session cart
    $response = $this->withSession([
        'cart' => [
            $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 1],
        ],
    ])->postJson(route('cart.promotion.apply'), [
        'code' => 'BOTANICAL15',
    ]);

    $response->assertOk();
    $response->assertJson([
        'applied_promotion' => [
            'id' => $promo->id,
            'code' => 'BOTANICAL15',
            'discount_amount' => 7.50,
        ],
    ]);

    expect(session('applied_promotion_id'))->toBe($promo->id);
});

test('applying new promotion replaces existing one enforcing strictly single promotion', function () {
    $product = Product::factory()->create([
        'price' => 100.00,
        'is_active' => true,
    ]);

    $promoA = Promotion::factory()->create([
        'name' => 'Offer A',
        'code' => 'OFFERA',
        'discount_type' => DiscountType::Percentage,
        'discount_value' => 10.00,
        'is_active' => true,
    ]);

    $promoB = Promotion::factory()->create([
        'name' => 'Offer B',
        'code' => 'OFFERB',
        'discount_type' => DiscountType::FixedAmount,
        'discount_value' => 25.00,
        'is_active' => true,
    ]);

    // Initial session with Promo A already applied
    $response = $this->withSession([
        'cart' => [
            $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 1],
        ],
        'applied_promotion_id' => $promoA->id,
    ])->postJson(route('cart.promotion.apply'), [
        'code' => 'OFFERB',
    ]);

    $response->assertOk();
    $response->assertJson([
        'applied_promotion' => [
            'id' => $promoB->id,
            'code' => 'OFFERB',
            'discount_amount' => 25.00,
        ],
    ]);

    // Session must only hold Promo B
    expect(session('applied_promotion_id'))->toBe($promoB->id);
});

test('cannot apply expired or invalid promotion code', function () {
    $product = Product::factory()->create([
        'price' => 50.00,
        'is_active' => true,
    ]);

    Promotion::factory()->create([
        'code' => 'EXPIRED20',
        'is_active' => true,
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->subDay(),
    ]);

    $response = $this->withSession([
        'cart' => [
            $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 1],
        ],
    ])->postJson(route('cart.promotion.apply'), [
        'code' => 'EXPIRED20',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['code']);
    expect(session('applied_promotion_id'))->toBeNull();
});

test('cannot apply promotion if subtotal is below minimum order amount', function () {
    $product = Product::factory()->create([
        'price' => 20.00,
        'is_active' => true,
    ]);

    Promotion::factory()->create([
        'code' => 'MIN50',
        'discount_type' => DiscountType::Percentage,
        'discount_value' => 10.00,
        'minimum_order_amount' => 50.00,
        'is_active' => true,
    ]);

    $response = $this->withSession([
        'cart' => [
            $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 1],
        ],
    ])->postJson(route('cart.promotion.apply'), [
        'code' => 'MIN50',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['code']);
    expect(session('applied_promotion_id'))->toBeNull();
});

test('user can remove active promotion from cart', function () {
    $product = Product::factory()->create([
        'price' => 50.00,
        'is_active' => true,
    ]);

    $promo = Promotion::factory()->create([
        'code' => 'SPRING10',
        'is_active' => true,
    ]);

    $response = $this->withSession([
        'cart' => [
            $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 1],
        ],
        'applied_promotion_id' => $promo->id,
    ])->deleteJson(route('cart.promotion.remove'));

    $response->assertOk();
    $response->assertJson([
        'applied_promotion' => null,
        'message' => 'Promotion removed from shopping bag.',
    ]);

    expect(session('applied_promotion_id'))->toBeNull();
});

test('cart sync removes promotion and warns user when subtotal drops below minimum order amount', function () {
    $productA = Product::factory()->create(['price' => 40.00, 'is_active' => true]);
    $productB = Product::factory()->create(['price' => 30.00, 'is_active' => true]);

    $promo = Promotion::factory()->create([
        'name' => 'Threshold Promo',
        'code' => 'THRESHOLD60',
        'discount_type' => DiscountType::Percentage,
        'discount_value' => 10.00,
        'minimum_order_amount' => 60.00,
        'is_active' => true,
    ]);

    // Cart originally has €70 subtotal and promo applied
    $response = $this->withSession([
        'cart' => [
            $productA->id => ['id' => $productA->id, 'product_id' => $productA->id, 'quantity' => 1],
            $productB->id => ['id' => $productB->id, 'product_id' => $productB->id, 'quantity' => 1],
        ],
        'applied_promotion_id' => $promo->id,
    ])->postJson(route('cart.sync'), [
        // Sync drops productB, new subtotal is only €40
        'items' => [
            ['product_id' => $productA->id, 'quantity' => 1],
        ],
    ]);

    $response->assertOk();
    $data = $response->json();

    expect($data['applied_promotion'])->toBeNull();
    expect((float) $data['summary']['discount_amount'])->toBe(0.0);
    expect((float) $data['summary']['total'])->toBe(40.0);
    expect($data['warnings'])->toContain("Promotion 'Threshold Promo' was removed because your bag subtotal is below the minimum €60.00.");
    expect(session('applied_promotion_id'))->toBeNull();
});

test('cart page renders applied promotion and available promotions', function () {
    $product = Product::factory()->create(['price' => 80.00, 'is_active' => true]);
    $promo = Promotion::factory()->create([
        'name' => 'Summer Glow',
        'code' => 'SUMMER20',
        'discount_type' => DiscountType::Percentage,
        'discount_value' => 20.00,
        'is_active' => true,
    ]);

    $this->withSession([
        'cart' => [
            $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 1],
        ],
        'applied_promotion_id' => $promo->id,
    ])->get(route('cart.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Cart/Index')
            ->where('appliedPromotion.id', $promo->id)
            ->where('appliedPromotion.discount_amount', fn ($val) => (float) $val === 16.0)
            ->where('summary.discount_amount', fn ($val) => (float) $val === 16.0)
            ->where('summary.total', fn ($val) => (float) $val === 64.0)
            ->has('availablePromotions')
        );
});

test('checkout page renders applied promotion and reflects discount in summary', function () {
    $delivery = DeliveryOption::factory()->create(['price' => 5.00]);
    $payment = PaymentOption::factory()->create();
    $product = Product::factory()->create(['price' => 100.00, 'is_active' => true]);

    $promo = Promotion::factory()->create([
        'name' => 'VIP Club',
        'code' => 'VIP10',
        'discount_type' => DiscountType::Percentage,
        'discount_value' => 10.00,
        'is_active' => true,
    ]);

    $this->withSession([
        'cart' => [
            $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 1],
        ],
        'applied_promotion_id' => $promo->id,
    ])->get(route('checkout.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Checkout/Index')
            ->where('appliedPromotion.id', $promo->id)
            ->where('appliedPromotion.discount_amount', fn ($val) => (float) $val === 10.0)
            ->where('summary.discount_amount', fn ($val) => (float) $val === 10.0)
            ->where('summary.total', fn ($val) => (float) $val === 90.0)
        );
});
