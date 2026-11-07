<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Widgets\OrdersStatsOverview;
use App\Filament\Widgets\RevenueTrendChart;
use App\Filament\Widgets\TopProductsTable;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Filament Dashboard Widgets', function () {
    beforeEach(function () {
        $this->systemRole = AdminRole::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Full administrative privileges.',
                'permissions' => ['*'],
                'is_system' => true,
            ]
        );

        $this->admin = AdminUser::factory()->create([
            'admin_role_id' => $this->systemRole->id,
            'is_active' => true,
        ]);
    });

    it('renders the admin dashboard successfully for authenticated admin', function () {
        $this->actingAs($this->admin, 'admin')
            ->get('/admin')
            ->assertOk();
    });

    it('calculates OrdersStatsOverview with optimized metrics', function () {
        $customer = User::factory()->create();
        $promotion = Promotion::factory()->create(['code' => 'SAVE10']);

        // Create a paid order today
        Order::factory()->create([
            'user_id' => $customer->id,
            'payment_status' => PaymentStatus::Paid,
            'status' => OrderStatus::Processing,
            'total' => 120.00,
            'discount_amount' => 15.00,
            'promotion_id' => $promotion->id,
            'created_at' => now(),
        ]);

        $this->actingAs($this->admin, 'admin');

        Livewire::test(OrdersStatsOverview::class)
            ->assertSuccessful()
            ->assertSee('Monthly Revenue')
            ->assertSee("Today's Sales")
            ->assertSee('Average Order Value')
            ->assertSee('Orders to Fulfill')
            ->assertSee('New Customers')
            ->assertSee('Promotions Savings');
    });

    it('calculates RevenueTrendChart with filter support', function () {
        Order::factory()->create([
            'payment_status' => PaymentStatus::Paid,
            'total' => 250.00,
            'created_at' => now(),
        ]);

        $this->actingAs($this->admin, 'admin');

        Livewire::test(RevenueTrendChart::class)
            ->assertSuccessful()
            ->set('filter', '7d')
            ->assertSuccessful()
            ->set('filter', '90d')
            ->assertSuccessful()
            ->set('filter', 'year')
            ->assertSuccessful();
    });

    it('renders TopProductsTable with bestselling items', function () {
        $product = Product::factory()->create([
            'name' => 'Luminescent Herbal Night Oil',
        ]);

        $order = Order::factory()->create([
            'payment_status' => PaymentStatus::Paid,
            'created_at' => now(),
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 4,
            'unit_price' => 45.00,
            'subtotal' => 180.00,
        ]);

        $this->actingAs($this->admin, 'admin');

        Livewire::test(TopProductsTable::class)
            ->assertSuccessful()
            ->assertSee('Luminescent Herbal Night Oil')
            ->assertSee('4 sold');
    });
});
