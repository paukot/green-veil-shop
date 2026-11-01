<?php

namespace App\Models;

use Database\Factories\SliderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $image
 * @property string|null $url
 * @property bool $url_opens_in_new_tab
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property string|null $title
 * @property string|null $subtitle
 * @property string|null $alt_text
 * @property string|null $button_label
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'image', 'url', 'url_opens_in_new_tab', 'is_active', 'sort_order',
    'starts_at', 'ends_at', 'title', 'subtitle', 'alt_text', 'button_label',
])]
class SliderItem extends Model
{
    /** @use HasFactory<SliderItemFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'url_opens_in_new_tab' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * Return slider items visible right now.
     *
     * @param  Builder<SliderItem>  $query
     * @return Builder<SliderItem>
     */
    public function scopeVisible(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
            ->orderBy('sort_order');
    }
}
