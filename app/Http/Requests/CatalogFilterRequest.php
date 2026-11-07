<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CatalogFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'brands' => ['nullable', 'string', 'max:255'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'in_stock' => ['nullable'],
            'sort' => ['nullable', 'string', 'max:50'],
            'skin_type' => ['nullable', 'string', 'max:100'],
            'skin_types' => ['nullable', 'string', 'max:255'],
            'hair_type' => ['nullable', 'string', 'max:100'],
            'hair_types' => ['nullable', 'string', 'max:255'],
            'effect' => ['nullable', 'string', 'max:100'],
            'effects' => ['nullable', 'string', 'max:255'],
            'ingredient' => ['nullable', 'string', 'max:100'],
            'ingredients' => ['nullable', 'string', 'max:255'],
            'claim' => ['nullable', 'string', 'max:100'],
            'claims' => ['nullable', 'string', 'max:255'],
            'classification' => ['nullable', 'string', 'max:100'],
            'classifications' => ['nullable', 'string', 'max:255'],
            'formula_type' => ['nullable', 'string', 'max:100'],
            'formula_types' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:50'],
            'genders' => ['nullable', 'string', 'max:100'],
            'spf' => ['nullable', 'string', 'max:50'],
            'manufactured_in' => ['nullable', 'string', 'max:100'],
            'origin' => ['nullable', 'string', 'max:100'],
            'origins' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Return filters for ProductCatalogService query building.
     *
     * @return array<string, mixed>
     */
    public function toQueryFilters(): array
    {
        return $this->only([
            'q',
            'category',
            'brand',
            'brands',
            'min_price',
            'max_price',
            'in_stock',
            'sort',
            'skin_type',
            'skin_types',
            'hair_type',
            'hair_types',
            'effect',
            'effects',
            'ingredient',
            'ingredients',
            'claim',
            'claims',
            'classification',
            'classifications',
            'formula_type',
            'formula_types',
            'gender',
            'genders',
            'spf',
            'manufactured_in',
            'origin',
            'origins',
        ]);
    }

    /**
     * Return normalized filter state payload for Inertia view.
     *
     * @return array<string, mixed>
     */
    public function toInertiaFilters(bool $includeCategory = false): array
    {
        $filters = [
            'q' => (string) $this->query('q', ''),
            'brands' => (string) $this->query('brands', $this->query('brand', '')),
            'skin_types' => (string) $this->query('skin_types', $this->query('skin_type', '')),
            'hair_types' => (string) $this->query('hair_types', $this->query('hair_type', '')),
            'effects' => (string) $this->query('effects', $this->query('effect', '')),
            'ingredients' => (string) $this->query('ingredients', $this->query('ingredient', '')),
            'claims' => (string) $this->query('claims', $this->query('claim', '')),
            'classifications' => (string) $this->query('classifications', $this->query('classification', '')),
            'formula_types' => (string) $this->query('formula_types', $this->query('formula_type', '')),
            'genders' => (string) $this->query('genders', $this->query('gender', '')),
            'spf' => (string) $this->query('spf', ''),
            'manufactured_in' => (string) $this->query('manufactured_in', $this->query('origin', '')),
            'min_price' => $this->query('min_price'),
            'max_price' => $this->query('max_price'),
            'in_stock' => $this->boolean('in_stock'),
            'sort' => (string) $this->query('sort', 'featured'),
        ];

        if ($includeCategory) {
            $filters['category'] = (string) $this->query('category', '');
        }

        return $filters;
    }
}
