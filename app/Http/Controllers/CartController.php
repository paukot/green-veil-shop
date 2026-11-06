<?php

namespace App\Http\Controllers;

use App\Http\Resources\CartItemResource;
use App\Http\Resources\DeliveryOptionResource;
use App\Models\DeliveryOption;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    public function index(Request $request): Response
    {
        $cartItems = $this->cartService->getItems($request);
        $summary = $this->cartService->getSummaryFromCartItems($cartItems);

        $standardDelivery = DeliveryOption::query()
            ->active()
            ->whereNotNull('free_threshold')
            ->orderBy('free_threshold')
            ->first();

        $freeShippingThreshold = $standardDelivery?->free_threshold ? (float) $standardDelivery->free_threshold : 50.00;
        $amountUntilFreeShipping = max(0, round($freeShippingThreshold - $summary['subtotal'], 2));

        $deliveryOptions = DeliveryOption::query()
            ->active()
            ->get()
            ->map(fn ($deliveryOption) => DeliveryOptionResource::make($deliveryOption)
                ->withSubtotal($summary['subtotal'])
            );

        $appliedPromo = $this->cartService->getAppliedPromotionData($request, $cartItems, $summary['subtotal']);
        $discountAmount = $appliedPromo ? $appliedPromo['discount_amount'] : 0.0;
        $orderTotal = max(0.0, round($summary['subtotal'] - $discountAmount, 2));

        $availablePromotions = $this->cartService->getAvailablePromotionsData($request, $cartItems, $summary['subtotal'], $appliedPromo['id'] ?? null);

        return Inertia::render('Cart/Index', [
            'items' => CartItemResource::collection($cartItems)->resolve(),
            'summary' => [
                'count' => $summary['count'],
                'subtotal' => $summary['subtotal'],
                'free_shipping_threshold' => $freeShippingThreshold,
                'amount_until_free_shipping' => (float) $amountUntilFreeShipping,
                'qualifies_for_free_shipping' => $summary['subtotal'] >= $freeShippingThreshold,
                'discount_amount' => $discountAmount,
                'total' => $orderTotal,
            ],
            'appliedPromotion' => $appliedPromo,
            'availablePromotions' => $availablePromotions,
            'deliveryOptions' => $deliveryOptions,
        ]);
    }

    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['present', 'array'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        /** @var array<int, array{product_id: int, quantity: int}> $items */
        $items = $validated['items'];

        $reconciliation = $this->cartService->syncCart($request, $items);

        return response()->json($reconciliation);
    }

    public function applyPromotion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:50'],
            'promotion_id' => ['nullable', 'integer', 'exists:promotions,id'],
        ]);

        $code = ! empty($validated['code']) ? trim((string) $validated['code']) : null;
        $promotionId = ! empty($validated['promotion_id']) ? (int) $validated['promotion_id'] : null;

        $result = $this->cartService->applyPromotion($request, $code, $promotionId);

        $cartItems = $this->cartService->getItems($request);
        $summary = $this->cartService->getSummaryFromCartItems($cartItems);
        $availablePromotions = $this->cartService->getAvailablePromotionsData(
            $request,
            $cartItems,
            $summary['subtotal'],
            $result['applied_promotion']['id'] ?? null
        );

        return response()->json([
            'applied_promotion' => $result['applied_promotion'],
            'available_promotions' => $availablePromotions,
            'message' => $result['message'],
        ]);
    }

    public function removePromotion(Request $request): JsonResponse
    {
        $this->cartService->removePromotion($request);

        $cartItems = $this->cartService->getItems($request);
        $summary = $this->cartService->getSummaryFromCartItems($cartItems);
        $availablePromotions = $this->cartService->getAvailablePromotionsData($request, $cartItems, $summary['subtotal'], null);

        return response()->json([
            'applied_promotion' => null,
            'available_promotions' => $availablePromotions,
            'message' => 'Promotion removed from shopping bag.',
        ]);
    }
}
