<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

use App\Traits\HasMorphMapTrait;

class ValidMorphItemRule implements ValidationRule
{
    use HasMorphMapTrait;

    protected array $items;

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        preg_match('/items\.(\d+)\.item_id/', $attribute, $matches);
        $index = $matches[1] ?? null;

        if (!is_numeric($index) || !isset($this->items[$index])) {
            $fail('العنصر غير صالح.');
            return;
        }

        $itemType = $this->items[$index]['item_type'] ?? null;
        $itemId   = $this->items[$index]['item_id'] ?? null;

        if (!$itemType || !$itemId || !array_key_exists($itemType, $this->getMorphMap())) {
            $fail("نوع العنصر غير معروف أو غير مدعوم.");
            return;
        }

        $modelClass = $this->getMorphClass($itemType);

        if (!$modelClass::where('id', $itemId)->exists()) {
            $fail("العنصر المحدد غير موجود.");
        }
    }
}
