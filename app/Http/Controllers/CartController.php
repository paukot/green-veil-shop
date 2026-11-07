<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cart\ApplyPromotionRequest;
use App\Http\Requests\Cart\SyncCartRequest;
use App\Http\Resources\CartItemResource;
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
        $overview = $this->cartService->getCartOverview($request);

        return Inertia::render('Cart/Index', [
            'items' => CartItemResource::collection($overview['items'])->resolve(),
            'summary' => $overview['summary'],
            'appliedPromotion' => $overview['applied_promotion'],
            'availablePromotions' => $overview['available_promotions'],
            'deliveryOptions' => $overview['delivery_options'],
        ]);
    }

    public function sync(SyncCartRequest $request): JsonResponse
    {
        $reconciliation = $this->cartService->syncCart($request, $request->cartItems());

        return response()->json($reconciliation);
    }

    public function applyPromotion(ApplyPromotionRequest $request): JsonResponse
    {
        $result = $this->cartService->applyPromotion(
            $request,
            $request->promotionCode(),
            $request->promotionId()
        );

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
