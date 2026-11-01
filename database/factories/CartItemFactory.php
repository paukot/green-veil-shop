<?php

namespace Database\Factories;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartItem>
 */
class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'session_id' => null,
            'product_id' => Product::factory(),
            'quantity' => 1,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => [
            'user_id' => $user->id,
            'session_id' => null,
        ]);
    }

    public function forProduct(Product $product, int $quantity = 1): static
    {
        return $this->state(fn () => [
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);
    }

    public function quantity(int $quantity): static
    {
        return $this->state(fn () => [
            'quantity' => $quantity,
        ]);
    }
}
