<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartItemController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    public function store(StoreCartItemRequest $request): RedirectResponse
    {
        $productId = (int) $request->validated('product_id');
        $quantity = (int) ($request->validated('quantity') ?? 1);

        $this->cartService->addItem($request, $productId, $quantity);

        return back()->with('success', 'Botanical product added to your bag.');
    }

    public function update(UpdateCartItemRequest $request, string $item): RedirectResponse
    {
        $quantity = (int) $request->validated('quantity');

        $this->cartService->updateItem($request, $item, $quantity);

        return back()->with('success', 'Bag updated.');
    }

    public function destroy(Request $request, string $item): RedirectResponse
    {
        $this->cartService->removeItem($request, $item);

        return back()->with('success', 'Item removed from your bag.');
    }
}
