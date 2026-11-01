<?php

namespace App\Http\Requests\Club;

use App\Models\Club\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('items') && is_array($this->input('items'))) {
            /** @var Order|null $order */
            $order = $this->route('order');
            $type = $this->input('type', $order?->type);

            if ($type === Order::TYPE_MATERIAL && ! $this->has('material_order_items')) {
                $this->merge(['material_order_items' => $this->input('items')]);
            } elseif ($type === Order::TYPE_APPAREL && ! $this->has('apparel_order_items')) {
                $this->merge(['apparel_order_items' => $this->input('items')]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', Rule::exists('tenant.categories', 'id')],
            'priority' => ['sometimes', 'string', 'in:low,normal,high,urgent'],
            'description' => ['nullable', 'string'],

            'apparel_order_items' => ['sometimes', 'array'],
            'apparel_order_items.*.player_id' => ['required_with:apparel_order_items', 'integer', Rule::exists('tenant.players', 'id')->whereNull('deleted_at')],
            'apparel_order_items.*.apparel_item_id' => ['required_with:apparel_order_items', 'integer', Rule::exists('tenant.apparel_items', 'id')->whereNull('deleted_at')],
            'apparel_order_items.*.size' => ['required_with:apparel_order_items', 'string', 'max:20'],
            'apparel_order_items.*.shirt_number' => ['nullable', 'integer', 'min:0', 'max:999'],
            'apparel_order_items.*.alias' => ['nullable', 'string', 'max:50'],
            'apparel_order_items.*.quantity' => ['required_with:apparel_order_items', 'integer', 'min:1'],
            'apparel_order_items.*.notes' => ['nullable', 'string', 'max:255'],

            'material_order_items' => ['sometimes', 'array'],
            'material_order_items.*.material_id' => ['required_with:material_order_items', 'integer', Rule::exists('tenant.materials', 'id')->whereNull('deleted_at')],
            'material_order_items.*.quantity' => ['required_with:material_order_items', 'numeric', 'gt:0'],
            'material_order_items.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Order|null $order */
                $order = $this->route('order');
                $type = $order?->type;

                if ($type === Order::TYPE_APPAREL && $this->filled('material_order_items')) {
                    $validator->errors()->add('material_order_items', 'Un pedido de indumentaria no puede incluir ítems de material.');
                }

                if ($type === Order::TYPE_MATERIAL && $this->filled('apparel_order_items')) {
                    $validator->errors()->add('apparel_order_items', 'Un pedido de materiales no puede incluir ítems de indumentaria.');
                }

                $access = $this->user()->clubAccess();
                $perm = $type === Order::TYPE_MATERIAL ? 'material_orders.update' : 'apparel_orders.update';

                if ($access->isOwnScope($perm) || $access->isOwnScope('orders.update')) {
                    if ($this->has('category_id')) {
                        $categoryId = $this->integer('category_id');
                        if (! $categoryId || ! in_array($categoryId, $access->ownCategoryIds(), true)) {
                            $validator->errors()->add('category_id', 'Solo podés asignar el pedido a tus categorías.');
                        }
                    }
                }
            },
        ];
    }
}
