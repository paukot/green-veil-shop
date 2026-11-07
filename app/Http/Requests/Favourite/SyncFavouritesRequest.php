<?php

namespace App\Http\Requests\Favourite;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SyncFavouritesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_ids' => ['present', 'array'],
            'product_ids.*' => ['integer'],
        ];
    }

    /**
     * @return array<int, int>
     */
    public function productIds(): array
    {
        /** @var array<int, int> $productIds */
        $productIds = $this->validated('product_ids', []);

        return $productIds;
    }
}
