<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Widgets\InventoryAlertsTable;
use App\Filament\Widgets\LatestOrdersTable;
use App\Filament\Widgets\OrdersStatsOverview;
use App\Filament\Widgets\RevenueTrendChart;
use App\Models\AdminUser;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Filament Dashboard Widgets', function () {
    beforeEach(function () {
        $this->admin = AdminUser::factory()->superAdmin()->create();
    });

    it('denies non-admin users from accessing dashboard', function () {
        $customer = User::factory()->create();

        $this->actingAs($customer, 'web')
            ->get('/admin')
            ->assertRedirect('/admin/login');
    });

    it('renders dashboard with all custom widgets for admin', function () {
        $this->actingAs($this->admin, 'admin')
            ->get('/admin')
            ->assertOk()
            ->assertSeeLivewire(OrdersStatsOverview::class)
            ->assertSeeLivewire(RevenueTrendChart::class)
            ->assertSeeLivewire(InventoryAlertsTable::class)
            ->assertSeeLivewire(LatestOrdersTable::class);
    });

    it('calculates correct revenue and fulfillment statistics in OrdersStatsOverview', function () {
        // Paid order within last 30 days (€50.00)
        Order::factory()->create([
            'payment_status' => PaymentStatus::Paid,
            'status' => OrderStatus::Processing,
            'total' => 50.00,
            'created_at' => now()->subDays(5),
        ]);

        // Another paid order within last 30 days (€30.00)
        Order::factory()->create([
            'payment_status' => PaymentStatus::Paid,
            'status' => OrderStatus::Processing,
            'total' => 30.00,
            'created_at' => now()->subDays(2),
        ]);

        // Unpaid pending order (€100.00) - must NOT be included in revenue
        Order::factory()->create([
            'payment_status' => PaymentStatus::Unpaid,
            'status' => OrderStatus::PendingPayment,
            'total' => 100.00,
            'created_at' => now()->subDay(),
        ]);

        // Old paid order older than 30 days (€200.00) - must NOT be in 30d revenue
        Order::factory()->create([
            'payment_status' => PaymentStatus::Paid,
            'status' => OrderStatus::Delivered,
            'total' => 200.00,
            'created_at' => now()->subDays(45),
        ]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(OrdersStatsOverview::class)
            ->assertSee('€80.00') // 50 + 30
            ->assertSee('€40.00') // AOV: 80 / 2
            ->assertSee('2') // 2 orders in Processing
            ->assertDontSee('€280.00');
    });

    it('renders low stock and out-of-stock products in InventoryAlertsTable', function () {
        $outOfStock = Product::factory()->create([
            'name' => 'Midnight Rose Elixir',
            'is_active' => true,
            'track_inventory' => true,
            'stock_quantity' => 0,
        ]);

        $lowStock = Product::factory()->create([
            'name' => 'Botanical Clay Mask',
            'is_active' => true,
            'track_inventory' => true,
            'stock_quantity' => 3,
        ]);

        $highStock = Product::factory()->create([
            'name' => 'Lavender Blossom Mist',
            'is_active' => true,
            'track_inventory' => true,
            'stock_quantity' => 45,
        ]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(InventoryAlertsTable::class)
            ->assertCanSeeTableRecords([$outOfStock, $lowStock])
            ->assertCanNotSeeTableRecords([$highStock])
            ->assertSee('Out of stock')
            ->assertSee('3 left');
    });

    it('renders recent orders in LatestOrdersTable', function () {
        $recentOrder = Order::factory()->create([
            'customer_name' => 'Clara Oswald',
            'total' => 64.50,
            'payment_status' => PaymentStatus::Paid,
            'status' => OrderStatus::Processing,
        ]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(LatestOrdersTable::class)
            ->assertCanSeeTableRecords([$recentOrder])
            ->assertSee($recentOrder->number)
            ->assertSee('Clara Oswald')
            ->assertSee('€64.50');
    });
});
